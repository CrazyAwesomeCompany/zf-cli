<?php

date_default_timezone_set('Europe/Amsterdam');

set_include_path(implode(PATH_SEPARATOR, array(
    realpath(dirname(__FILE__) . '/../library'),
    get_include_path(),
)));

if (!function_exists("readline")) {
    function readline($prompt)
    {
        // Read the line from the keyboard
        echo trim($prompt) . " ";
        return trim(fgets(STDIN));
    }
}

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

if (!defined('APPLICATION_ENV')) {
    // Check if command line env variable is set
    foreach ($_SERVER['argv'] as $arg) {
        if (strpos($arg, '--env') === 0) {
            $arg = explode('=', $arg, 2);

            define('APPLICATION_ENV', $arg[1]);
            break;
        }
    }

    // Check if still not defined and ask for environment
    if (!defined('APPLICATION_ENV')) {
        $env = readline('Please provide the application environment: [production]');
        if (!$env)
            $env = 'production';

        define('APPLICATION_ENV', $env);
    }

}

