<?php

use PicPerf\Breakdown\Commands\BreakDown;
use Symfony\Component\Console\Application;

$app = new Application('BreakDown', '7.0.0');

$app->add(new BreakDown());

$app->setDefaultCommand("breakdown", true);

$app->run();
