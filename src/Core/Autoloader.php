<?php

declare(strict_types=1);

namespace FlowForge\Core;

/**
 * Built-in PSR-4 compliant autoloader.
 * Ensures FlowForge operates out of the box with zero external dependencies.
 */
class Autoloader
{
    /**
     * @var array<string, string>
     */
    private static array $prefixes = [];

    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        $baseDir = dirname(__DIR__, 2);
        self::addPrefix('FlowForge\\', $baseDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR);
        self::addPrefix('App\\', $baseDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR);

        spl_autoload_register([self::class, 'loadClass']);
        self::$registered = true;
    }

    public static function addPrefix(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        self::$prefixes[$prefix] = $baseDir;
    }

    public static function loadClass(string $class): bool
    {
        foreach (self::$prefixes as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }

            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }

        return false;
    }
}
