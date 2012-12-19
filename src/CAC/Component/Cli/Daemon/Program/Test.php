<?php


namespace CAC\Cli\Daemon\Program;


class Test extends \CAC\Cli\Daemon
{

    protected $defaultConfiguration = array(
        //'runStart' => '20:15',
        //'runEnd' => '23:32',
        'interval' => 10,
        'maxInterval' => 300
    );



    public function getName()
    {
        return "daemon:cac:test";
    }

    public function getDescription()
    {
        return "This is a test daemon";
    }

    protected function execute()
    {
        $this->output("Test daemon run");
    }



}