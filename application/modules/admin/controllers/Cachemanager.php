<?php

use MX\MX_Controller;

class Cachemanager extends MX_Controller
{
    private array $itemMatches;
    private array $minifyMatches;
    private array $websiteMatches;

    public function __construct()
    {
        // Make sure to load the administrator library!
        $this->load->library('administrator');

        $this->itemMatches = ["spells/*", "items/*", "search/*"];
        $this->minifyMatches = ["minify/*"];
        $this->websiteMatches = ["*.cache"];

        parent::__construct();

        requirePermission("viewCache");
    }

    public function index()
    {
        // Change the title
        $this->administrator->setTitle("Manage cache");

        // Prepare my data
        $data = array(
            'url' => $this->template->page_url
        );

        // Load my view
        $output = $this->template->loadPage("cachemanager/cache.tpl", $data);

        // Put my view in the main box with a headline
        $content = $this->administrator->box('Manage cache', $output);

        // Output my content. The method accepts the same arguments as template->view
        $this->administrator->view($content, false, "modules/admin/js/cache.js");
    }

    public function get()
    {
        $item = $this->countItemCache();
        $website = $this->countWebsiteCache();
        $theme = $this->countThemeMinifyCache();

        $total['files'] = $item['files'] + $website['files'] + $theme['files'];
        $total['size'] = $this->formatSize($item['size'] + $website['size'] + $theme['size']);

        // Prepare my data
        $data = [
            'url' => $this->template->page_url,
            'item' => $item,
            'website' => $website,
            'theme' => $theme,
            'total' => $total
        ];

        // Load my view
        $output = $this->template->loadPage("cachemanager/cache_data.tpl", $data);

        die($output);
    }

    private function countItemCache(): array
    {
        // Define our result
        $result = [
            "files" => 0,
            "size" => 0
        ];

        // Define what to search for
        $matches = $this->itemMatches;

        // Loop through all searches
        return $this->SearchCache($matches, $result);
    }

    private function countThemeMinifyCache(): array
    {
        // Define our result
        $result = [
            "files" => 0,
            "size" => 0
        ];

        // Define what to search for
        $matches = $this->minifyMatches;

        // Loop through all searches
        return $this->SearchCache($matches, $result);
    }

    private function countWebsiteCache(): array
    {
        // Define our result
        $result = [
            "files" => 0,
            "size" => 0
        ];

        // Define what to search for
        $matches = $this->websiteMatches;

        // Loop through all searches
        return $this->SearchCache($matches, $result);
    }

    private function formatSize($size): string
    {
        if ($size < 1024) {
            return $size . " B";
        } elseif ($size < 1024 * 1024) {
            return round($size / 1024) . " KB";
        } elseif ($size < 1024 * 1024 * 1024) {
            return round($size / (1024 * 1024)) . " MB";
        } else {
            return round($size / (1024 * 1024 * 1024)) . " GB";
        }
    }

    public function delete($type = false)
    {
        requirePermission("emptyCache");

        if (!in_array($type, ['item', 'website', 'theme', 'all'])) {
            die();
        } else {
            switch ($type) {
                case "item":
                    foreach ($this->itemMatches as $match) {
                        $this->cache->delete($match);
                    }
                    break;

                case "website":
                    foreach ($this->websiteMatches as $match) {
                        $this->cache->delete($match);
                    }
                    break;

                case "theme":
                    foreach ($this->minifyMatches as $match) {
                        $this->cache->delete($match);
                    }
                    break;

                case "all":
                    foreach ($this->itemMatches as $match) {
                        $this->cache->delete($match);
                    }
                    foreach ($this->websiteMatches as $match) {
                        $this->cache->delete($match);
                    }
                    foreach ($this->minifyMatches as $match) {
                        $this->cache->delete($match);
                    }
                    break;
            }

            die("success");
        }
    }

    /**
     * Get size of cache
     *
     * @param array $matches
     * @param array $result
     * @return array
     */
    private function SearchCache(array $matches, array $result): array
    {
        foreach ($matches as $search) {
            // Search for matches
            $matches = glob("writable/cache/data/" . $search);

            if ($matches) {
                // Loop through all matches
                foreach ($matches as $file) {
                    if (!preg_match("/index\.html/", $file)) {
                        // Count and add their size to the result
                        $result['files']++;
                        $result['size'] += filesize($file);
                    }
                }
            }
        }

        $result['sizeString'] = $this->formatSize($result['size']);

        return $result;
    }
}
