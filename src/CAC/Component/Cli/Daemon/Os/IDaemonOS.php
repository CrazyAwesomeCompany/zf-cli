<?php
/**
 * The \Cac\Cli\Daemon\Os\IDaemonOS interface file
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
 * Interface for Operating System specific daemons
 *
 * This interface provides the methods OS specific daemons must implement
 * before they can be used.
 *
 * @package    CrazyAwesomeCompany
 * @subpackage Daemon
 */
interface IDaemonOS
{

    /**
     * Make the script a real daemon. Send dispatch messages it is started
     *
     * @param $daemon
     */
    function daemonize(Daemon $daemon);

    /**
     * Get the current status of the daemon
     *
     * @param $daemon
     */
    function getStatus(Daemon $daemon);

    /**
     * Get the requirements for PHP on the server to run daemons
     *
     * @return array List with requirements
     */
    function getRequirements();

    /**
     * Check if the daemon should be running
     *
     * @param $daemon
     *
     * @return bool True when still running, False when stopping
     */
    function isRunning(Daemon $daemon);

    /**
     * Start the daemon
     *
     * @param $daemon The daemon program to start
     */
    function start(Daemon $daemon);

    /**
     * Stop the daemon
     *
     * @param $daemon The daemon program to stop
     */
    function stop(Daemon $daemon);

}
