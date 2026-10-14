<?php

namespace App\Utils\Route;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

abstract class RouteUtils
{
    /**
     * Loops through a folder and requires all PHP files
     * Searches sub-directories as well.
     */
    public static function includeFilesInFolder($folder): void
    {
        try {
            $rdi = new RecursiveDirectoryIterator($folder);
            $it = new RecursiveIteratorIterator($rdi);

            while ($it->valid()) {
                if (! $it->isDot() && $it->isFile() && $it->isReadable() && $it->current()->getExtension() === 'php') {
                    require $it->key();
                }

                $it->next();
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Loops through a folder and requires all PHP files
     * Searches sub-directories as well.
     */
    public static function includeRouteFiles($folder): void
    {
        self::includeFilesInFolder($folder);
    }
}
