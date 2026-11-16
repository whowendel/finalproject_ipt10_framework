<?php

declare(strict_types=1);

namespace FlowForge\Core;

/**
 * Application configuration registry with dot-notation access,
 * e.g. Config::get('database.driver', 'sqlite').
 */
final class Config
{
    private static ?array $items = null;

    public static function load(string $configFile): void
    {
        self::$items = is_file($configFile) ? (array)require $configFile : [];
    }

    public static function set(array $items): void
    {
        self::$items = $items;
    }

    public static function all(): array
    {
        if (self::$items === null) {
            self::load(dirname(__DIR__, 2) . '/config/app.php');
        }
        return self::$items ?? [];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::all();
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
