<?php namespace CodeIgniter\Debug;

/**
 * Debug Toolbar
 *
 * Displays a toolbar with bits of stats to aid a developer in debugging.
 *
 * Inspiration: http://prophiler.fabfuel.de
 *
 * @package CodeIgniter\Debug
 */
class Toolbar
{
    /**
     * Collectors to be used and displayed.
     *
     * @var array
     */
    protected $collectors = [];

    //--------------------------------------------------------------------

    public function __construct()
    {
        get_instance()->load->language('profiler');
        $toolbarCollectors = [
            'CodeIgniter\Debug\Toolbar\Collectors\Timers',
		    'CodeIgniter\Debug\Toolbar\Collectors\DB',
            'CodeIgniter\Debug\Toolbar\Collectors\Logs',
//      'CodeIgniter\Debug\Toolbar\Collectors\Views',
//		'CodeIgniter\Debug\Toolbar\Collectors\Request',
//		'CodeIgniter\Debug\Toolbar\Collectors\Response',
//		'CodeIgniter\Debug\Toolbar\Collectors\Cache',
            'CodeIgniter\Debug\Toolbar\Collectors\Files',
            'CodeIgniter\Debug\Toolbar\Collectors\Configs',
        ];

        foreach ($toolbarCollectors as $collector)
        {
            if ( ! class_exists($collector))
            {
                // @todo Log this!
                continue;
            }

            $this->collectors[] = new $collector();
        }
    }

    //--------------------------------------------------------------------

    public function run(): string
    {
        // Data items used within the view.
        $collectors = $this->collectors;

        global $totalTime, $startMemory, $request, $response;
        $totalTime       = $totalTime * 1000;
        $totalMemory     = number_format((memory_get_peak_usage() - $startMemory) / 1048576, 3);
        $segmentDuration = $this->roundTo($totalTime / 7, 5);
        $segmentCount    = (int)ceil($totalTime / $segmentDuration);
        $varData         = $this->collectVarData();

        ob_start();
        include(dirname(__FILE__).'/Toolbar/View/toolbar.tpl.php');
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    //--------------------------------------------------------------------

    /**
     * Called within the view to display the timeline itself.
     *
     * @return string
     */
    protected function renderTimeline(int $segmentCount, int $segmentDuration, int $totalTime): string
    {
        global $startTime;
        $displayTime = $segmentCount * $segmentDuration;

        $rows = $this->collectTimelineData();

        $output = '';

        foreach ($rows as $row)
        {
            $output .= "<tr>";
            $output .= "<td>{$row['name']}</td>";
            $output .= "<td>{$row['component']}</td>";
            $output .= "<td style='text-align: right'>".number_format($row['duration'] * 1000, 2)." ms</td>";
            $output .= "<td colspan='{$segmentCount}' style='overflow: hidden'>";

            $offset = ((($row['start'] - $startTime) * 1000) / $displayTime) * 100;
            $length = (($row['duration'] * 1000) / $displayTime) * 100;

            $output .= "<span class='timer' style='left: {$offset}%; width: {$length}%;' title='".number_format($length, 2)."%'></span>";

            $output .= "</td>";

            $output .= "</tr>";
        }

        return $output;
    }

    //--------------------------------------------------------------------

    /**
     * Returns a sorted array of timeline data arrays from the collectors.
     *
     * @return array
     */
    protected function collectTimelineData(): array
    {
        $data = [];

        // Collect it
        foreach ($this->collectors as $collector)
        {
            if (! $collector->hasTimelineData())
            {
                continue;
            }

            $data = array_merge($data, $collector->timelineData());
        }

        // Sort it


        return $data;
    }

    //--------------------------------------------------------------------

    /**
     * Returns an array of data from all of the modules
     * that should be displayed in the 'Vars' tab.
     *
     * @return array
     */
    protected function collectVarData() : array
    {
        $data = [];

        foreach ($this->collectors as $collector)
        {
            if (! $collector->hasVarData())
            {
                continue;
            }

            $data = array_merge($data, $collector->getVarData());
        }

        return $data;
    }

    //--------------------------------------------------------------------

    /**
     * Rounds a number to the nearest incremental value.
     *
     * @param     $number
     * @param int $increments
     *
     * @return float
     */
    protected function roundTo($number, $increments = 5)
    {
        $increments = 1 / $increments;

        return (ceil($number * $increments) / $increments);
    }

    //--------------------------------------------------------------------

}
