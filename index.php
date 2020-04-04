#!/usr/bin/env php
<?php
define('strict_types', 1);
// application.php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use tomasfejfar\GitTrain\RebaseCommand;

$application = new Application();
$defaultCommand = new RebaseCommand();
$application->add($defaultCommand);
$application->setDefaultCommand($defaultCommand->getName());

$application->run();
