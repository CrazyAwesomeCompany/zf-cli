<?php
// Crazy Awesome Command Line Tool
ini_set('max_execution_time', 0);

require_once __DIR__ . '/bootstrap.php';

require_once 'CAC/Cli.php';

$cli = new \CAC\Cli($_SERVER['argv']);

$cli->bootstrap()->run();

