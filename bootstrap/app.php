<?php

use App\Bootstrap;
use LaravelZero\Framework\Application;

// Ensure required directories exist
Bootstrap::ensureDirectoriesExist();

return Application::configure(basePath: dirname(__DIR__))->create();
