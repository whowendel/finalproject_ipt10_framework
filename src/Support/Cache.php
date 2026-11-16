<?php

declare(strict_types=1);

namespace FlowForge\Support;

/**
 * File-based key/value cache with expiry, stored as JSON in storage/cache.
 * Used to avoid calling external APIs on every page view.
 */
final class Cache
{
    public function __construct(private string $directory)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->file($key);
        if (!is_file($file)) {
            return $default;
        }

        $payload = json_decode((string)@file_get_contents($file), true);
        if (!is_array($payload) || ($payload['expires_at'] ?? 0) < time()) {
            @unlink($file);
            return $default;
        }

        return $payload['value'] ?? $default;
    }

    public function put(string $key, mixed $value, int $ttlSeconds): void
    {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0775, true);
        }

        @file_put_contents($this->file($key), json_encode([
            'expires_at' => time() + $ttlSeconds,
            'value' => $value,
        ]), LOCK_EX);
    }

    public function forget(string $key): void
    {
        @unlink($this->file($key));
    }

    private function file(string $key): string
    {
        return rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . sha1($key) . '.json';
    }
}
