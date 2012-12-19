<?php
/**
 * The \Cac\Cli\Daemon\Factory class file
 *
 * @copyright Copyright (c) 2011, Crazy Awesome Company
 * @link      www.crazyawesomecompany.com
 * @version   $Revision$
 * @author    Nick de Groot <nick@crazyawesomecompany.com>
 * @package   CrazyAwesomeCompany
 */

namespace CAC\Component\Cli\Daemon;

use CAC\Component\Cli\Daemon;

/**
 * Factory class for executing daemons
 *
 * Use this factory to start daemons based on the running operating system
 *
 * @package    CrazyAwesomeCompany
 * @subpackage Daemon
 */
class Factory
{

    /**
     * Holder for the Operating System
     *
     * @var string
     */
    static protected $os;

    /**
     * Holder for the Operating System object
     *
     * @var \CAC\Cli\Daemon\Os\IDaemonOS
     */
    static protected $osObject;

    /**
     * Start the daemon
     *
     * @param \CAC\Cli\Daemon $daemon The daemon
     */
    static public function start(Daemon $daemon)
    {
        $os = self::determineOs();

        if ($os == 'Windows') {
            // Windows registers the daemon as a service...
            // We can do this through the web
            $osObject = self::getOsDaemon();
            $osObject->start($daemon);
        } else {
            // Linux needs the CLI
            $cmd = realpath(APPLICATION_PATH . '/../tools/cacli.php') . ' ' . $daemon->getName() . ' -verbose --env=' . APPLICATION_ENV;
            $pid =  shell_exec('php ' . $cmd); // Do the command
        }
    }

    /**
     * Stop the daemon
     *
     * @param \CAC\Cli\Daemon $daemon The daemon
     */
    static public function stop(Daemon $daemon)
    {
        $osObject = self::getOsDaemon();
        $osObject->stop($daemon);
    }

    /**
     * Get the specific requirements for the Daemon based on the operating system
     *
     * @param String $os The operation system
     *
     * @return array The requirements
     */
    static public function getRequirements($os = null)
    {
        if (!$os) {
            $os = self::determineOs();
        }

        // Get the real daemon os
        $osObject = self::getOsDaemon($os);

        $requirements = $osObject->getRequirements();

        return $requirements;
    }

    /**
     * Get the used operation system
     *
     * @return string The operation system
     */
    static public function determineOs()
    {
        if (!self::$os) {
            if(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
                self::$os = "Windows";
            } else {
                self::$os = PHP_OS;
            }
        }
        return self::$os;
    }

    /**
     * Get the Operating System specific daemon
     *
     * @param string $os The operating system name
     *
     * @return IDaemonOS The Daemon
     * @throws Exception
     */
    static public function getOsDaemon($os = null)
    {
        if (!self::$osObject) {
            if (!$os) {
                $os = self::determineOs();
            }

            if (!file_exists(__DIR__ . '/Os/' . $os . '.php')) {
                throw new Exception(
                    "Operation System `" . $os . "` not (yet) supported"
                );
            }

            require_once __DIR__ . '/Os/' . $os . '.php';
            $className = '\\CAC\\Cli\\Daemon\\Os\\' . $os;

            self::$osObject = new $className();
        }

        return self::$osObject;
    }

}
