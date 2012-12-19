<?php
/**
 * The \Cac\Cli\Daemon\Util class file
 *
 * @copyright Copyright (c) 2011, Crazy Awesome Company
 * @link      www.crazyawesomecompany.com
 * @version   $Revision$
 * @author    Nick de Groot <nick@crazyawesomecompany.com>
 * @package   CrazyAwesomeCompany
 */

namespace CAC\Component\Cli\Daemon;

/**
 * Utility class for daemons
 *
 * @package    CrazyAwesomeCompany
 * @subpackage Daemon
 */
class Util
{

    /**
     * Get a daemon by name
     *
     * @param string $name Name of the daemon
     *
     * @return array The daemon information
     */
    static public function getDaemon($name)
    {
        $daemons = self::getAllDaemons();

        $daemon = null;

        foreach ($daemons as $d) {
            if ($d['name'] == $name) {
                $daemon = $d;
                break;
            }
        }

        return $daemon;
    }

    /**
     * Get all available daemons
     *
     * @return array The daemons
     */
    static public function getAllDaemons($options = null)
    {
        if (!$options) {
            // get all daemon
            $front = \Zend_Controller_Front::getInstance();
            $bootstrap = $front->getParam('bootstrap');

            $options = $bootstrap->getOptions();
        }

        $daemons = array();

        if (isset($options['system']['daemons'])) {
            // We have real daemons in our application
            foreach ($options['system']['daemons'] as $daemonLocation) {
                // Read the files in the directory
                $files = glob(realpath($daemonLocation['directory']) . "/*.php");

                foreach ($files as $filename) {
                    include_once $filename; // include the file

                    $className = pathinfo($filename, PATHINFO_FILENAME);
                    if (isset($daemonLocation['namespace'])) {
                        $className = $daemonLocation['namespace'] . "\\" . $className;
                    }

                    if (!class_exists($className)) {
                        continue;
                    }

                    $class = new $className();

                    // We only want real daemons
                    if ($class instanceof \CAC\Cli\Daemon) {
                        $daemon = array();
                        $daemon['name'] = $class->getName();
                        $daemon['class'] = $className;
                        $daemon['description'] = $class->getDescription();
                        $daemon['configuration'] = $class->getConfig();
                        $daemon['logDir'] = null;
                        if (isset($daemonLocation['logDir'])) {
                            $daemon['logDir'] = $daemonLocation['logDir'];
                            $class->setLogFile($daemonLocation['logDir']);
                        }
                        $daemon['object'] = $class;

                        $daemons[] = $daemon;
                    }
                }
            }
        }

        return $daemons;
    }




}