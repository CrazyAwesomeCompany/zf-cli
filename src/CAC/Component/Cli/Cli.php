<?php
/**
 * The \Cac\Cli class file
 *
 * @copyright Copyright (c) 2011, Crazy Awesome Company
 * @link      www.crazyawesomecompany.com
 * @version   $Revision$
 * @author    Nick de Groot <nick@crazyawesomecompany.com>
 * @package   CrazyAwesomeCompany
 */

namespace CAC\Component\Cli;

use CAC\Component\Cli\Daemon\Util;

/**
 * Class for running command line programs with the Zend Framework available
 *
 * This class will load the programs given to it or by defining the
 * program directory.
 *
 * @package    CrazyAwesomeCompany
 * @subpackage Cli
 */
class Cli extends Base
{

    /**
     * The version of the application
     *
     * @var string
     */
    private $version = "0.5-BETA";

    /**
     * The Zend Application
     *
     * @var Zend_Application
     * @ignore
     */
    protected $application;

    /**
     * Holder for the programs
     *
     * @var array
     * @ignore
     */
    protected $programs = array();

    /**
     * {@inheritdoc}
     * @see \CAC\Cli\Base::run()
     */
    public function run()
    {
        // Show an introduction
        $this->msg(str_repeat("*", 80));
        $this->msg("** Crazy Awesome Command Line Tool");
        $this->msg("** - Author: Nick de Groot <nick@crazyawesomecompany.com>");
        $this->msg("** - Version: " . $this->version);
        $this->msg("** - PHP Info: " . \PHP_VERSION . ", Memory limit: " . ini_get('memory_limit') . ", Max Execution Time: " . ini_get('max_execution_time'));
        $this->msg("**");
        $this->msg(str_repeat("*", 80));
        $this->msg();

        if (!$this->hasOption('_program')) {
            echo $this->getUsageMessage();
            return;
        }

        if($this->getOption('_program') == 'help') {
            if ($this->hasArgument(0) && array_key_exists($this->getArgument(0), $this->programs)) {
                // show program help
                $this->programs[$this->getArgument(0)]->getUsageMessage();
                return;
            }
            $this->getUsageMessage();
            return;
        }

        // Check if we have the program
        if (!$this->hasProgram($this->getOption('_program'))) {
            // Program not found
            $this->msg("ERROR: Program not found!");
            $this->msg();
            $this->getUsageMessage();
            return;
        }

        // Run the program
        $program = $this->getProgram($this->getOption('_program'));
        $program->setArguments($this->args);
        $program->setOptions($this->options);
        $program->setVerbose($this->verbose);
        $program->bootstrap()->run();
    }

    /**
     * {@inheritdoc}
     * @see \CAC\Cli\Base::getUsageMessage()
     */
    public function getUsageMessage()
    {
        $this->msg("Usage: cacli program [options]");
        $this->msg();
        ksort($this->programs);
        foreach ($this->programs as $name => $program) {
            $this->msg(str_pad($name, 40) . "\t" . $program->getDescription());
        }
    }

    /**
     * Bootstrap the cli
     *
     * @return Cli
     */
    public function bootstrap()
    {
        parent::bootstrap();

        // Create application, and only bootstrap it
        $this->application = new \Zend_Application(
            APPLICATION_ENV,
            APPLICATION_PATH . '/configs/application.ini'
        );

        $this->application->bootstrap();

        $this->readPrograms();

        return $this;
    }

    /**
     * Load the programs into the cli based on the directory
     *
     */
    private function readPrograms()
    {

        $options = $this->application->getOptions();

        $daemons = Util::getAllDaemons($options);

        foreach ($daemons as $daemon) {
            $this->addProgram($daemon['object']);
        }

        if (isset($options['system']['programs'])) {
            // We have real programs in our application
            foreach ($options['system']['programs'] as $programLocation) {
                // Read the files in the directory
                $files = glob(realpath($programLocation['directory']) . "/*.php");

                foreach ($files as $filename) {
                    include_once $filename; // include the file

                    $className = pathinfo($filename, PATHINFO_FILENAME);
                    if (isset($programLocation['namespace'])) {
                        $className = $programLocation['namespace'] . "\\" . $className;
                    }

                    if (!class_exists($className)) {
                        continue;
                    }

                    $reflectionClass = new \ReflectionClass($className);
                    if ($reflectionClass->isAbstract()) {
                        continue;
                    }

                    $class = new $className();

                    // We only want real programs
                    if ($class instanceof Program) {
                        $this->addProgram($class);
                    }
                }
            }
        }

    }

    /**
     * Add a program to the cli
     *
     * @param Program $program The program
     */
    public function addProgram(Program $program)
    {
        $this->programs[$program->getName()] = $program;
    }

    /**
     * Check if the program exists
     *
     * @param string $name The name of the program
     *
     * @return bool
     */
    public function hasProgram($name)
    {
        return array_key_exists($name, $this->programs);
    }

    /**
     * Get a program
     *
     * @param string $name The name of the program
     *
     * @return Program|false
     */
    public function getProgram($name)
    {
        if (!$this->hasProgram($name)) {
            return false;
        }
        return $this->programs[$name];
    }

}

