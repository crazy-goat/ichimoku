#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';

use CrazyGoat\Forex\DownloadCandles;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new DownloadCandles());
$application->run();