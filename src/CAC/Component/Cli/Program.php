<?php
/**
 * The \Cac\Cli\Program class file
 *
 * @copyright Copyright (c) 2011, Crazy Awesome Company
 * @link      www.crazyawesomecompany.com
 * @version   $Revision$
 * @author    Nick de Groot <nick@crazyawesomecompany.com>
 * @package   CrazyAwesomeCompany
 */

namespace CAC\Component\Cli;

/**
 * Abstract Program Base class for command line programs
 *
 * @pacakge    CrazyAwesomeCompany
 * @subpackage Cli
 */
abstract class Program extends Base
{

    /**
     * The log file of the program
     *
     * @var string
     * @ignore
     */
    protected $logFile;

    protected $configuration = array();
    protected $defaultConfiguration = array();

    /**
     * The name of the program
     *
     * @return string
     */
    abstract function getName();

    /**
     * The short description of the program
     *
     * @return string
     */
    abstract function getDescription();

    /**
     * Set the log file to print output in
     *
     * @param string $directory The directory of the log files
     * @param string $file      [optional] The filename to use, otherwise it will use program name
     *
     * @return string The log file location
     */
    public function setLogFile($directory, $file = null)
    {
        if (!$file) {
            $file = $this->getName() . ".log";
        }

        // sanatize the filename
        $file = $this->sanatize($file);

        $directory = realpath($directory);
        if ($directory) {
            $this->logFile = $directory . \DIRECTORY_SEPARATOR . $file;
        }

        return $this->logFile;
    }

    public function getLogFile()
    {
        return $this->logFile;
    }

    /**
     * Remove special characters from the name
     *
     * @param string $name The string to sanatize
     *
     * @return string The sanatized string
     */
    public function sanatize($name)
    {
        $specialChars = array ("#","$","%","^","&","*","!","~","�","\"","�","'","=","?","/","[","]","(",")","|","<",">",";",":","\\",",");
        return str_replace($specialChars, "_", $name);
    }

    /**
     * Print output into a log file when available
     *
     * @param string $message The message to show
     */
    protected function printOutput($message)
    {
        if ($this->logFile) {
            file_put_contents($this->logFile, $message, \FILE_APPEND);
        } else {
            parent::printOutput($message);
        }
    }

    /**
     * Read a line from the keyboard
     *
     * @param string $prompt Message to prompt for
     *
     * @return string The keyboard input
     */
    protected function readline($prompt)
    {
        // Read the line from the keyboard
        echo trim($prompt) . " ";
        return trim(fgets(STDIN));
    }

    /**
     * Get the Daemon configuration
     *
     * @return array The configuration
     */
    public function getConfig()
    {
        if (!$this->configuration) {
            $this->getDatabaseConfiguration();
        }

        return $this->configuration;
    }

    /**
     * Set the configuration and store it
     *
     * @param array $config The configuration
     * @param bool  $store  Store the configuration into the database
     */
    public function setConfig(array $config, $store = false)
    {
        $this->configuration = $config;

        if ($store) {
            $values = array('configuration' => serialize($config));
            $connection = \Zend_Registry::get('doctrine')->getConnection();
            $connection->update('daemon', $values, array('name' => $this->getName()));
        }
    }

    /**
     * Get the configuration from the database
     *
     * @return array False when it cannot be found
     */
    protected function getDatabaseConfiguration()
    {
        $query = "SELECT configuration FROM daemon WHERE name = ?";

        $connection = \Zend_Registry::get('doctrine')->getConnection();

        $config = $connection->fetchColumn($query, array($this->getName()));

        if (!$config) {
            $this->configuration = $this->defaultConfiguration;
            return false;
        }

        $config = unserialize($config);
        $this->configuration = array_merge($this->defaultConfiguration, $config);

        return $this->configuration;
    }

    protected function closeConnections()
    {
        $connection = $this->getConnection();
        $connectionLegacy = $this->getConnection('legacy');

        $connection->close();
        $connectionLegacy->close();
    }


}
