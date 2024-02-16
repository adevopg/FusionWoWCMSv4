<?php

if(file_exists("install") && !file_exists("install/.lock"))
{
    header("Location: install");
    die();
}

// Used by the debug toolbar. Do not remove.
$startMemory = memory_get_usage();

/*
 *---------------------------------------------------------------
 * APPLICATION ENVIRONMENT
 *---------------------------------------------------------------
 *
 * You can load different configurations depending on your
 * current environment. Setting the environment also influences
 * things like logging and error reporting.
 *
 * This can be set to anything, but default usage is:
 *
 *     development
 *     testing
 *     production
 *
 * NOTE: If you change these, also change the error_reporting() code below
 *
 */

define('ENVIRONMENT', $_SERVER['CI_ENV'] ?? 'development');
/*
 *---------------------------------------------------------------
 * ERROR REPORTING
 *---------------------------------------------------------------
 *
 * Different environments will require different levels of error reporting.
 * By default development will show errors but testing and live will hide them.
 */

if (defined('ENVIRONMENT'))
{
    switch (ENVIRONMENT)
    {
        case 'development':
            error_reporting(-1);
            ini_set('display_errors', 1);
            define('CI_DEBUG', 1);
            break;

        case 'testing':
        case 'production':
            ini_set('display_errors', 0);
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
            define('CI_DEBUG', 0);
            break;

        default:
            header('HTTP/1.1 503 Service Unavailable.', true, 503);
            echo 'The application environment is not set correctly.';
            exit(1); // EXIT_ERROR
    }
}

/*
 *---------------------------------------------------------------
 * SYSTEM FOLDER NAME
 *---------------------------------------------------------------
 *
 * This variable must contain the name of your "system" folder.
 * Include the path if the folder is not in the same  directory
 * as this file.
 *
 */
$system_path = 'system';

/*
 *---------------------------------------------------------------
 * APPLICATION FOLDER NAME
 *---------------------------------------------------------------
 *
 * If you want this front controller to use a different "application"
 * folder then the default one you can set its name here. The folder
 * can also be renamed or relocated anywhere on your server.  If
 * you do, use a full server path. For more info please see the user guide:
 * http://codeigniter.com/user_guide/general/managing_apps.html
 *
 * NO TRAILING SLASH!
 *
 */
$application_folder = 'application';

/*
 * ---------------------------------------------------------------
 * WRITABLE DIRECTORY NAME
 * ---------------------------------------------------------------
 *
 * This variable must contain the name of your "writable" directory.
 * The writable directory allows you to group all directories that
 * need write permission to a single place that can be tucked away
 * for maximum security, keeping it out of the application and/or
 * system directories.
 */
$writable_directory = 'writable';

/*
 *---------------------------------------------------------------
 * VIEW DIRECTORY NAME
 *---------------------------------------------------------------
 *
 * If you want to move the view directory out of the application
 * directory, set the path to it here. The directory can be renamed
 * and relocated anywhere on your server. If blank, it will default
 * to the standard location inside your application directory.
 * If you do move this, use an absolute (full) server path.
 *
 * NO TRAILING SLASH!
 */
$view_folder = '';


// --------------------------------------------------------------------
// END OF USER CONFIGURABLE SETTINGS.  DO NOT EDIT BELOW THIS LINE
// --------------------------------------------------------------------

/*
 * ---------------------------------------------------------------
 *  Resolve the system path for increased reliability
 * ---------------------------------------------------------------
 */

// Set the current directory correctly for CLI requests
if (defined('STDIN'))
{
    chdir(dirname(__FILE__));
}

if (($_temp = realpath($system_path)) !== FALSE)
{
    $system_path = $_temp.'/';
}
else
{
    // Ensure there's a trailing slash
    $system_path = rtrim($system_path, '/').'/';
}

// Is the system path correct?
if (! is_dir($system_path))
{
    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo 'Your system folder path does not appear to be set correctly. Please open the following file and correct this: '.pathinfo(__FILE__, PATHINFO_BASENAME);
    exit(3); // EXIT_CONFIG
}

/*
 * -------------------------------------------------------------------
 *  Now that we know the path, set the main path constants
 * -------------------------------------------------------------------
 */
// The name of THIS file
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

// Path to the system folder
define('BASEPATH', str_replace('\\', '/', $system_path));

// Path to the front controller (this file)
define('FCPATH', dirname(__FILE__).'/');

// Path to the writable directory.
define('WRITEPATH', realpath(str_replace('\\', '/', $writable_directory)).'/');

// Name of the "system folder"
define('SYSDIR', trim(strrchr(trim(BASEPATH, '/'), '/'), '/'));

// The path to the "application" folder
if (is_dir($application_folder))
{
    define('APPPATH', $application_folder.'/');
}
else
{
    if (! is_dir(BASEPATH.$application_folder.'/'))
    {
        header('HTTP/1.1 503 Service Unavailable.', true, 503);
        echo 'Your application folder path does not appear to be set correctly. Please open the following file and correct this: '.SELF;
        exit(3); // EXIT_CONFIG
    }

    define('APPPATH', BASEPATH.$application_folder.'/');
}

// The path to the "views" directory
if (! isset($view_folder[0]) && is_dir(APPPATH.'views'.DIRECTORY_SEPARATOR))
{
    $view_folder = APPPATH.'views';
}
elseif (is_dir($view_folder))
{
    if (($_temp = realpath($view_folder)) !== false)
    {
        $view_folder = $_temp;
    }
    else
    {
        $view_folder = strtr(
            rtrim($view_folder, '/\\'),
            '/\\',
            DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
        );
    }
}
elseif (is_dir(APPPATH.$view_folder.DIRECTORY_SEPARATOR))
{
    $view_folder = APPPATH.strtr(
            trim($view_folder, '/\\'), '/\\', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
}
else
{
    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo 'Your view folder path does not appear to be set correctly. Please open the following file and correct this: '.SELF;
    exit(3); // EXIT_CONFIG
}

define('VIEWPATH', $view_folder.DIRECTORY_SEPARATOR);

/*
 * --------------------------------------------------------------------
 * LOAD THE BOOTSTRAP FILE
 * --------------------------------------------------------------------
 *
 * And away we go...
 *
 */
require BASEPATH.'CodeIgniter.php';

/* End of file index.php */
/* Location: ./index.php */
