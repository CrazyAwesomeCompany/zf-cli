<?php
/**
 * The \Cac\Cli\Base class file
 *
 * @copyright Copyright (c) 2011, Crazy Awesome Company
 * @link      www.crazyawesomecompany.com
 * @version   $Revision$
 * @author    Nick de Groot <nick@crazyawesomecompany.com>
 * @package   CrazyAwesomeCompany
 */

namespace CAC\Component\Cli;

/**
 * Abstract class for command line programs
 *
 * @package    CrazyAwesomeCompany
 * @subpackage Cli
 */
abstract class Base
{

    /**
     * Output verbose message flag
     *
     * @var int
     * @ignore
     */
    const OUTPUT_VERBOSE = 2;

    /**
     * Output normal message flag
     *
     * @var int
     * @ignore
     */
    const OUTPUT_NORMAL = 1;

    /**
     * Show verbose output
     *
     * @var bool
     */
    protected $verbose = false;

    /**
     * Holder for arguments
     *
     * @var array
     * @ignore
     */
    protected $args = array();

    /**
     * Holder for options
     *
     * @var array
     * @ignore
     */
    protected $options = array();

    /**
     * Holder for timers
     *
     * @var array
     */
    protected $timers = array();

    /**
     * The constructor
     *
     * @param array $args All the arguments and options to set
     */
    public function __construct($args = array())
    {
        $this->setArguments($args);
        $this->parse($args);
    }

    /**
     * Show how to use the program
     */
    abstract public function getUsageMessage();

    /**
     * Run the program
     */
    abstract function run();

    /**
     * Bootstrap the program
     *
     * This is an empty method and can be overridden by the real program
     *
     * @return Base
     */
    public function bootstrap()
    {
        // Set custom error handler
        //set_error_handler(array($this, 'phpErrors'), E_ALL);

        return $this;
    }

    /**
     * Parse the command line parameters
     *
     */
    protected function parse($args = array())
    {
        $count = count($args);
        for ($x = 0; $x < $count; $x++) {
            if ($x === 0) {
                $this->setOption('_baseProgram', $args[$x]);
                continue;
            }

            if (!$this->hasOption('_program') && strpos($args[$x], '-') !== 0) {
                $this->setOption('_program', $args[$x]);
                continue;
            }

            // Check if we need to output verbose messages
            if ($args[$x] == '-verbose') {
                $this->verbose = true;
                continue;
            }

            if (strpos($args[$x], '-') === 0) {
                $option = substr($args[$x], 1);
                $option = explode("=", $option);
                if (count($option) == 2) {
                    $this->setOption($option[0], $option[1]);
                } else {
                    $this->setOption($option[0], true);
                }
                continue;
            }

            // Add as a program argument
            $this->addArgument($args[$x]);
        }

    }

    /**
     * Check if an argument is set
     *
     * @param int $index The argument number
     *
     * @return bool
     */
    protected function hasArgument($index)
    {
        return array_key_exists($index, $this->args);
    }

    /**
     * Get an argument
     *
     * @param int $index The argument index
     *
     * @return mixed
     */
    protected function getArgument($index)
    {
        return $this->args[$index];
    }

    /**
     * Add an argument
     *
     * @param mixed $value The argument value
     */
    protected function addArgument($value)
    {
        $this->args[] = $value;
    }

    /**
     * Set the arguments. This will overwrite all previous arguments
     *
     * @param array $args The arguments
     */
    public function setArguments(array $args)
    {
        $this->args = $args;
    }

    /**
     * Check if an option is set
     *
     * @param string $name The option name
     *
     * @return bool
     */
    protected function hasOption($name)
    {
        return isset($this->options[$name]);
    }

    /**
     * Get an option value
     *
     * @param string $name The option name
     */
    protected function getOption($name)
    {
        if (!$this->hasOption($name))
            return false;
        return $this->options[$name];
    }

    /**
     * Set an option
     *
     * @param string $name  The option name
     * @param mixed  $value The option value
     */
    protected function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * Set the options. This will overwrite all previous options
     *
     * @param array $options The options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Catches PHP Errors and forwards them to log function
     *
     * @param integer $errno   Level
     * @param string  $errstr  Error
     * @param string  $errfile File
     * @param integer $errline Line
     *
     * @return boolean
     */
    public function phpErrors($errno, $errstr, $errfile, $errline)
    {
        // Ignore suppressed errors (prefixed by '@')
        if (error_reporting() == 0) {
            return;
        }

        $this->output('[PHP ' . $errno . '] ' . $errstr . ' in file ' . $errfile . ' on line ' . $errline);

        return true;
    }

    /**
     * Output a message
     *
     * @param string $message The message to output
     * @param int    $type    The output type [verbose|normal]
     * @param mixed  $eol     The end of line character to use
     * @param bool   $useDate Add a date in the message
     */
    public function output($message = '', $type = self::OUTPUT_NORMAL, $eol = \PHP_EOL, $useDate = true)
    {
        if ($this->verbose || ($type === self::OUTPUT_NORMAL)) {
            if ($useDate) {
                $dt = new \DateTime();
                $this->printOutput($dt->format("Y-m-d H:i:s") . " |\t");
            }
            print $this->printOutput($message . $eol);
        }
    }

    /**
     * Print a message to the screen
     *
     * @param string $message The message to print
     */
    protected function printOutput($message)
    {
        print $message;
    }

    /**
     * Output a direct message to the screen without time
     *
     * @param string $message The message
     * @param mixed  $eol     The end of line character to use
     */
    public function msg($message = '', $eol = \PHP_EOL)
    {
        $this->output($message, self::OUTPUT_NORMAL, $eol, false);
    }

    /**
     * Set the verbose
     *
     * @param bool $verbose
     */
    public function setVerbose($verbose)
    {
        $this->verbose = (bool) $verbose;
    }

    /**
     * Output a verbose message to the screen
     *
     * @param string $message The message
     * @param mixed  $eol     The end of line character to use
     * @param bool   $useDate Add a datetime in front of the message
     */
    public function verbose($message = '', $eol = \PHP_EOL, $useDate = true)
    {
        $this->output($message, self::OUTPUT_VERBOSE, $eol, $useDate);
    }

    /**
     * Get current memory usage of the script
     *
     * @return string
     */
    public function getMemoryUsage()
    {
        $size = memory_get_usage();
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))), 2).' '.$unit[$i];
    }

    /**
     * Start the timer
     *
     * @param string $name Name of the timer
     */
    public function startTimer($name = 'default')
    {
        $start = microtime(true);
        $this->timers[$name] = array('start' => $start, 'end' => false);
    }

    /**
     * Stop the timer
     *
     * @param string $name Name of the timer
     *
     * @return float The timing
     */
    public function stopTimer($name = 'default')
    {
        if (!array_key_exists($name, $this->timers)) {
            return false;
        }
        $end = microtime(true);
        $this->timers[$name]['end'] = $end;

        return $this->getTiming($name);
    }

    /**
     * Get a Doctrine connection
     *
     * @param string $name Name of the connection
     *
     * @return \Doctrine\DBAL\Connection
     */
    protected function getConnection($name = 'default')
    {
        $connection = \Zend_Registry::get('doctrine')->getConnection($name);
        return $connection;
    }

    /**
     * Return the timing in seconds
     *
     * @param string $name Name of the timer
     *
     * @return float Time in seconds with 2 precision
     */
    public function getTiming($name = 'default')
    {
        if (!array_key_exists($name, $this->timers)) {
            return false;
        }
        return round(($this->timers[$name]['end'] - $this->timers[$name]['start']), 2);
    }

}
