<?php
/**
 * The \Cac\Cli\Daemon class file
 *
 * @copyright Copyright (c) 2011, Crazy Awesome Company
 * @link      www.crazyawesomecompany.com
 * @version   $Revision$
 * @author    Nick de Groot <nick@crazyawesomecompany.com>
 * @package   CrazyAwesomeCompany
 */

namespace CAC\Component\Cli;

use CAC\Cli\Daemon\Factory;

/**
 * Abstract Daemon Base class for daemons
 *
 * @package    CrazyAwesomeCompany
 * @subpackage Daemon
 */
abstract class Daemon extends Program
{

    private $interval = 10;
    private $maxInterval = 100;

    protected $os;

    abstract protected function execute();

    /**
     * Show a daemon usage message
     */
    final public function getUsageMessage()
    {
        $this->output('This is a daemon!');
    }

    /**
     * Bootstrap the Daemon
     *
     */
    final public function bootstrap()
    {
        parent::bootstrap();

        // Get the os daemon
        $this->os = Factory::getOsDaemon();

        $config = $this->getConfig();

        if (isset($config['interval']))
            $this->setInterval($config['interval']);

        if (isset($config['maxInterval']))
            $this->maxInterval = $config['maxInterval'];
        else
            $this->maxInterval = ($this->interval * 10);

        return $this;
    }

    /**
     * Start running the Daemon
     *
     */
    final public function run()
    {
        // before start running, register the daemon so we can track it
        $this->registerDaemon();

        // Before we push the process into a daemon make sure the connection
        // to the database server is closed
        \Zend_Registry::get('doctrine')->getConnection()->close();

        $this->output("Starting daemon " . $this->getName());

        // Start daemonizing the daemon
        $this->os->daemonize($this);

        gc_enable();

        while(true) {
            // Check if we should run the daemon
            if (!$this->os->isRunning($this)) {
                // stop the daemon
                break;
            }

            // give a heartbeat
            $this->sendHeartbeat();

            // Only execute when in time range
            if ($this->isInTimeRange())
                $this->execute();

            // clear caches
            clearstatcache();
            gc_collect_cycles();
            flush();

            // Wait for some time
            $this->verbose("Sleeping for " . floor($this->interval) . " seconds");
            sleep(floor($this->interval));
        }

        $this->output("Shutting down daemon " . $this->getName());

        // Unregister the daemon
        $this->unregisterDaemon();

        // Let the daemon die
        $this->os->dieDaemon($this);
    }

    /**
     * Test a connection
     *
     * @param unknown_type $connection
     * @param unknown_type $name
     * @return unknown
     *
     * @todo Change the test to a better one
     */
    protected function testConnection($connection, $name = 'default')
    {
        $this->verbose("Testing connections...");
        try {
            switch ($name) {
                default:
                    $connection->executeQuery("SELECT COUNT(name) FROM daemon");
                    break;
            }
        } catch (\PDOException $e) {
            // Probably the connection has gone away
            $this->output('DB Error: ' . $e->getMessage());
            $this->output('Reconnection database');

            $this->closeConnections();
            $connection = $this->getConnection($name);
        }

        return $connection;
    }

    /**
     * Check if the daemon may execute by checking the time range
     *
     * @return bool True when in time range
     */
    private function isInTimeRange()
    {
        $config = $this->getConfig();
        // When begin or end is not set it is always in time range
        if (!isset($config['runStart']) || !isset($config['runEnd'])) {
            return true;
        }

        $begin = strtotime($config['runStart']);
        $end = strtotime($config['runEnd']);

        // Check if the end is before the begin. If so add one day
        // to the end time
        if ($end < $begin)
            $end += (60 * 60 * 24);

        $now = time();

        if ($now < $end && $now > $begin) {
            return true;
        }

        return false;
    }

    /**
     * Slow down execution of the daemon
     *
     */
    protected function slowDown()
    {
        $newInterval = $this->interval * 1.3;

        if ($newInterval >= $this->maxInterval)
            $newInterval = $this->maxInterval;

        $this->setInterval($newInterval);
    }

    /**
     * Speed up execution of the daemon to normal speed
     *
     */
    protected function speedUp()
    {
        $config = $this->getConfig();

        if (isset($config['interval']))
            $this->setInterval($config['interval']);
        else
            $this->setInterval(10);
    }

    /**
     * Set the interval between runs
     *
     * @param int $interval Seconds to wait
     */
    protected function setInterval($interval)
    {
        $this->interval = $interval;
    }

    /**
     * Give a heartbeat
     *
     */
    private function sendHeartbeat()
    {
        // Send heartbeat to database
        $connection = \Zend_Registry::get('doctrine')->getConnection();

        $values = array('heartbeat' => date('Y-m-d H:i:s'));

        $connection->update('daemon', $values, array('name' => $this->getName()));
    }

    /**
     * Get the status of the current daemon
     *
     * @return array
     */
    public function getStatus()
    {
        $connection = \Zend_Registry::get('doctrine')->getConnection();

        $times = array('started' => null, 'heartbeat' => null);
        $result = $connection->fetchAssoc(
            "SELECT started, heartbeat FROM daemon WHERE name = ?",
            array($this->getName())
        );

        $times = array_merge($times, (array) $result);

        return $times;
    }



    /**
     * Register the daemon
     */
    private function registerDaemon()
    {
        $connection = \Zend_Registry::get('doctrine')->getConnection();

        if ($this->getDatabaseConfiguration() === false) {
            // Insert the daemon
            $query = "INSERT INTO daemon SET name = ?, configuration = ?, started = ?, heartbeat = NULL";
            $params = array($this->getName(), serialize($this->configuration), date('Y-m-d H:i:s'));
        } else {
            $query = "UPDATE daemon SET started = ? WHERE name = ?";
            $params = array(date('Y-m-d H:i:s'), $this->getName());
        }

        $connection->executeQuery($query, $params);
    }

    /**
     * Unregister the daemon
     */
    private function unregisterDaemon()
    {
        $query = "UPDATE daemon SET started = NULL, heartbeat = NULL WHERE name = ?";

        $connection = \Zend_Registry::get('doctrine')->getConnection();
        $connection->executeQuery($query, array($this->getName()));
    }

}
