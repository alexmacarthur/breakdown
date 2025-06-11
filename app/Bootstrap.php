<?php

namespace PicPerf\Breakdown;

class Bootstrap
{
    /**
     * Ensure all required directories exist
     */
    public static function ensureDirectoriesExist()
    {
        $homeDir = $_SERVER['HOME'].'/.breakdown';

        // Create the main .breakdown directory if it doesn't exist
        if (! is_dir($homeDir)) {
            mkdir($homeDir, 0755, true);
        }

        // Create the cache directory
        $cacheDir = $homeDir.'/cache';
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Create the views directory
        $viewsDir = $homeDir.'/views';
        if (! is_dir($viewsDir)) {
            mkdir($viewsDir, 0755, true);
        }

        return true;
    }
}
