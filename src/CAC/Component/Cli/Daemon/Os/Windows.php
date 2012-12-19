<?php
/**
 * The \Cac\Cli\Daemon\Os\Windows class file
 *
 * @copyright Copyright (c) 2011, Crazy Awesome Company
 * @link      www.crazyawesomecompany.com
 * @version   $Revision$
 * @author    Nick de Groot <nick@crazyawesomecompany.com>
 * @package   CrazyAwesomeCompany
 */

namespace CAC\Component\Cli\Daemon\Os;

use CAC\Component\Cli\Daemon;

/**
 * Windows specific daemon script
 *
 * This daemon script uses the `win32service` extension to run it's daemons
 * as services.
 *
 * @package    CrazyAwesomeCompany
 * @subpackage Daemon
 */
class Windows implements IDaemonOS
{

    public function start(Daemon $daemon)
    {
        if (!$this->isRegistered($daemon)) {
            $this->register($daemon);
        }

        switch ($this->getStatus($daemon)) {
            case \WIN32_SERVICE_RUNNING:
            case \WIN32_SERVICE_START_PENDING:
            case \WIN32_SERVICE_CONTINUE_PENDING:
                throw new \CAC\Cli\Daemon\Exception(
                    "Daemon already starting/running"
                );

                break;

            case \WIN32_SERVICE_PAUSED:
            case \WIN32_SERVICE_PAUSE_PENDING:
                throw new \CAC\Cli\Daemon\Exception(
                    "Daemon is paused. Please use the resume command"
                );

                break;

        }

        $x = win32_start_service($daemon->getName());

    }

    public function stop(Daemon $daemon)
    {
        $x = win32_stop_service($daemon->getName());


        $this->unregister($daemon);
    }

    public function daemonize(Daemon $daemon)
    {
        // Send a dispatcher message
        $x = win32_start_service_ctrl_dispatcher('PHP Daemon - ' . $daemon->getName());
    }

    public function dieDaemon(Daemon $daemon)
    {

    }

    public function isRunning(Daemon $daemon)
    {
        // Switch on the last control message dispatched
        switch (win32_get_last_control_message()) {
            case WIN32_SERVICE_CONTROL_CONTINUE:
                // continue running
                break;

            case WIN32_SERVICE_CONTROL_INTERROGATE:
                // report back the current status
                win32_set_service_status(WIN32_NO_ERROR);
                break;

            case WIN32_SERVICE_CONTROL_STOP:
            case WIN32_SERVICE_CONTROL_SHUTDOWN:
                // Stop the service
                win32_set_service_status(WIN32_SERVICE_STOPPED);

                return false;

                break;

            default:
                win32_set_service_status(WIN32_ERROR_CALL_NOT_IMPLEMENTED);
                break;
        }
        return true;
    }

    public function getStatus(Daemon $daemon)
    {
        $status = win32_query_service_status($daemon->getName());

        // When not registered the call is returning error code 1060
        if ($status === 1060)
            return -1;

        return $status['CurrentState'];
    }

    protected function isRegistered($daemon)
    {
        if ($this->getStatus($daemon) === -1) {
            return false;
        }
        return true;
    }

    protected function register($daemon)
    {
        $config = \Zend_Registry::get('config')->application;

        $x = win32_create_service(array(
            'service' => $daemon->getName(),
            'display' => 'PHP Daemon - ' . $daemon->getName(),
            'description' => $daemon->getDescription(), # long description
            'path' => $config->system->phpBin . 'php.exe',
            'params' => '"' . realpath(APPLICATION_PATH . '/../tools/cacli.php') . '"  ' . $daemon->getName() . ' --env=' . APPLICATION_ENV,
        ));

        var_dump($x);
    }

    protected function unregister($daemon)
    {
        $x = win32_delete_service($daemon->getName());
        debug_zval_dump($x);
    }


    public function getRequirements()
    {
        $requirements = array();

        $requirements[] = array(
            'name' => 'win32service',
            'description' => 'The windows32service extension',
            'status' => (int) extension_loaded('win32service')
        );

        return $requirements;
    }



}