<?php

use LaravelZero\Framework\Application;
use PicPerf\Breakdown\Bootstrap;

Bootstrap::ensureDirectoriesExist();

return Application::configure(basePath: dirname(__DIR__))->create();
