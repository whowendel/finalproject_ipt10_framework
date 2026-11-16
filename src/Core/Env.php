<?php

declare(strict_types=1);

namespace FlowForge\Core;

/**
 * Minimal .env loader.
 *
 * Reads KEY=VALUE pairs from a .env file in the project root (falling back to
 * .env.example so a fresh checkout still boots). Real environment variables
 * always win over file values, so the same code works on a server that sets
 * variables in its own configuration.
 */
final class Env
{
    /** @var array<string, string> */
    private static array $values = [];
    private static bool $loaded = false;

    public static function load(string $basePath, bool $force = false): void
    {
        if (self::$loaded && !$force) {
            return;
        }

        self::$values = [];
        $basePath = rtrim($basePath, '/\\');
        foreach (['.env', '.env.example'] as $file) {
            $path = $basePath . DIRECTORY_SEPARATOR . $file;
            if (is_file($path)) {
                self::$values = self::parse((string)file_get_contents($path));
                break;
            }
        }

        self::$loaded = true;
    }

    /**
     * Parse .env file contents into an associative array.
     *
     * @return array<string, string>
     */
    public static function parse(string $contents): array
    {
        $values = [];
        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if (str_starts_with($key, 'export ')) {
                $key = trim(substr($key, 7));
            }
            if (!preg_match('/^[A-Z_][A-Z0-9_]*$/i', $key)) {
                continue;
            }

            $quote = $value[0] ?? '';
            if (($quote === '"' || $quote === "'") && str_ends_with($value, $quote) && strlen($value) >= 2) {
                $value = substr($value, 1, -1);
            } else {
                // Strip trailing inline comments on unquoted values: KEY=value # note
                $value = trim((string)preg_replace('/\s+#.*$/', '', $value));
            }

            $values[$key] = $value;
        }

        return $values;
    }

    /**
     * Get an environment value, casting "true"/"false"/"null"/"" to PHP types.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? self::$values[$key] ?? null;
        }

        if ($value === null) {
            return $default;
        }

        return match (strtolower((string)$value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            '', '(empty)' => $default,
            default => $value,
        };
    }

    /**
     * Override values (used by tests).
     *
     * @param array<string, string> $values
     */
    public static function set(array $values): void
    {
        self::$values = array_merge(self::$values, $values);
        self::$loaded = true;
    }
}
