<?php

namespace App\Core;

class Autoloader
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        spl_autoload_register([__CLASS__, 'autoload']);
        self::$registered = true;
    }

    public static function autoload(string $className): void
    {
        $prefix = 'App\\';
        $baseDir = dirname(__DIR__) . '/';

        $len = strlen($prefix);
        if (strncmp($prefix, $className, $len) !== 0) {
            return;
        }

        $relativeClass = substr($className, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}
