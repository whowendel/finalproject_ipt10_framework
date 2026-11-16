<?php

declare(strict_types=1);

namespace FlowForge\Core;

/**
 * URL generator aware of the application's base path.
 *
 * Lets the app run from the domain root (php -S ... -t public) or from a
 * sub-folder such as http://localhost/IPT10%20Final%20Project/ under XAMPP.
 */
final class Url
{
    private static string $basePath = '';
    private static string $currentPath = '/';

    public static function setBasePath(string $basePath): void
    {
        $basePath = '/' . trim(str_replace('\\', '/', $basePath), '/');
        self::$basePath = $basePath === '/' ? '' : $basePath;
    }

    public static function basePath(): string
    {
        return self::$basePath;
    }

    public static function setCurrentPath(string $path): void
    {
        self::$currentPath = $path;
    }

    public static function currentPath(): string
    {
        return self::$currentPath;
    }

    /**
     * Build an application URL. Url::to() returns the base path itself,
     * Url::to('/requests') returns e.g. "/my-app/requests".
     */
    public static function to(string $path = ''): string
    {
        if ($path === '') {
            return self::$basePath;
        }
        if (preg_match('#^([a-z][a-z0-9+.-]*:|//)#i', $path)) {
            return $path; // already absolute
        }
        return self::$basePath . '/' . ltrim($path, '/');
    }

    public static function asset(string $path): string
    {
        return self::to('/assets/' . ltrim($path, '/'));
    }

    /**
     * Work out which prefix of the request path belongs to the install folder.
     *
     * @param string $scriptName  $_SERVER['SCRIPT_NAME'], e.g. "/app/public/index.php"
     * @param string $requestPath decoded request path, e.g. "/app/requests/3"
     */
    public static function detectBasePath(string $scriptName, string $requestPath): string
    {
        $dir = str_replace('\\', '/', dirname($scriptName));
        if ($dir === '/' || $dir === '.' || $dir === '') {
            return '';
        }

        // Try ".../public" first, then the project folder itself (root .htaccess rewrites into public/).
        $candidates = [$dir];
        if (str_ends_with($dir, '/public')) {
            $candidates[] = substr($dir, 0, -strlen('/public'));
        }

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && ($requestPath === $candidate || str_starts_with($requestPath, $candidate . '/'))) {
                return $candidate;
            }
        }

        return '';
    }
}
