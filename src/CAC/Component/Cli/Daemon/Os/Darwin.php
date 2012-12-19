<?php
/**
 * The \Cac\Cli\Daemon\Os\Darwin class file
 *
 * @copyright Copyright (c) 2011, Crazy Awesome Company
 * @link      www.crazyawesomecompany.com
 * @version   $Revision$
 * @author    Mark Seinen <info@sein-it.nl>
 * @package   CrazyAwesomeCompany
 */

namespace CAC\Cli\Daemon\Os;

use CAC\Component\Cli\Daemon;

use CAC\Component\Cli\Daemon\Exception;

/**
 * Darwin (MacOs) specific daemon script
 *
 * This daemon script uses the `pcntl` and `posix` extensions to run it's daemons
 * This is an exact copy of the linux OS Daemon
 *
 * @package    CrazyAwesomeCompany
 * @subpackage Daemon
 */
class Darwin implements IDaemonOS
{

    protected $pidLocation;

    private $killDaemon = false;


    protected $sigHandlers = array(
        1 => array(__CLASS__, 'sigHandler'),
        15 => array(__CLASS__, 'sigHandler')
    );

    public function __construct()
    {
        $this->pidLocation = '/var/run';
    }


    public function start(Daemon $daemon)
    {
        // we can just bootstrap and run the daemon here

    }

    public function stop(Daemon $daemon)
    {
        $pid = $this->getPid($daemon);

        if (!$pid) {
            throw new Exception(
                "Cannot find pid. Is daemon running?"
            );
        }

        // Send the signal to stop
        if (!posix_kill($pid, \SIGTERM)) {
            throw new Exception(
                "Failed to send stop signal"
            );
        }
    }

    /**
     * (non-PHPDoc)
     * @see IDaemonOS
     *
     * @param \CAC\Cli\Daemon $daemon
     */
    public function daemonize(Daemon $daemon)
    {
        // Fork the process
        $pid = pcntl_fork();
        if ($pid === -1) {
            // Error
            echo 'Process could not be forked';
        } else if ($pid) {
            // Parent
            echo "Process forked to PID: " . $pid;

            exit();
        } else {
            // Child
            $processId = posix_getpid();

            // Change umask
            @umask(0);

            if (!$this->writePid($this->getPidFilename($daemon), $processId)) {
                throw new Exception("Could not write pid file");
            }

            declare(ticks = 1);

            $this->setupSigHandlers();


            return true;
        }
    }

    public function sigHandler($signo)
    {
        switch ($signo) {
            case \SIGTERM:
                // Handle shutdown tasks
                $this->killDaemon = true;
                break;
            case \SIGHUP:
                // Handle restart tasks

                break;
            case \SIGCHLD:
                // A child process has died

                while (pcntl_wait($status, WNOHANG OR WUNTRACED) > 0) {
                    usleep(1000);
                }
                break;
            default:
                // Handle all other signals
                break;
        }

    }



    public function getStatus(Daemon $daemon)
    {

    }

    public function isRunning(Daemon $daemon)
    {
        // Check if we should kill the daemon
        if ($this->killDaemon) {
            return false;
        }

        $pid = $this->getPid($daemon);

	if ($pid === false) {
	    throw new Exception(
		"Daemon pid file not found"
            );
	}

        // Ping app
        if (!posix_kill(intval($pid), 0)) {
            // Not responding so unlink pidfile
            @unlink($pidFile);
            throw new Exception(
                "Daemon not responding"
            );
        }

        return true;
    }

    public function dieDaemon(Daemon $daemon)
    {
        $pidFile = $this->getPidFilename($daemon);
        $pid = file_get_contents($pidFile);

        @unlink($pidFile);

        // Do the actual kill
        passthru('kill -9 ' . $pid);
    }

    protected function setupSigHandlers()
    {
        foreach ($this->sigHandlers as $signal => $handler) {
            pcntl_signal($signal, $handler);
        }
    }

    protected function writePid($pidFilePath, $pid = null)
    {
        if (empty($pid)) {
            return false;
        }

        $pidDirectory = dirname($pidFilePath);

        if (!file_exists($pidDirectory)) {
            if (!mkdir($pidDirectory, 0775, true)) {
                throw new Exception(
                    "Could not make pid file directory. " . $pidDirectory
                );
            }
        } else if (!is_writable($pidDirectory)) {
            chmod($pidDirectory, 0775);
        }

        if (!file_put_contents($pidFilePath, $pid)) {
            throw new Exception(
                "Could not write pid file"
            );
        }

        if (!chmod($pidFilePath, 0644)) {
            throw new Exception(
                "Could not chmod pid file"
            );
        }

        return true;
    }

    public function getPidFilename(Daemon $daemon)
    {
        $daemonName = $daemon->sanatize($daemon->getName());

        return $this->pidLocation . '/' . $daemonName . '/' . $daemonName . '.pid';
    }

    public function getPid(Daemon $daemon)
    {
        // Check the pid file
        $pidFile = $this->getPidFilename($daemon);

        if (!file_exists($pidFile)) {
            unset($pidFile);
            return false;
        }

        $pid = file_get_contents($pidFile);
        if (!$pid) {
            return false;
        }

        return $pid;
    }



    public function getRequirements()
    {
        $requirements = array();

        $requirements[] = array(
            'name' => 'pcntl',
            'description' => 'The pcntl extension',
            'status' => (int) extension_loaded('pcntl')
        );

        $requirements[] = array(
            'name' => 'posix',
            'description' => 'The posix extension',
            'status' => (int) extension_loaded('posix')
        );

        return $requirements;
    }



}
