<?php

declare(strict_types=1);

namespace FlowForge\Support;

use FlowForge\Core\Config;

/**
 * Tiny file logger: one line per entry in storage/logs/app.log.
 *   [2026-11-16 21:04:11] WARNING: Holiday API unavailable {"status":503}
 */
final class Logger
{
    private static ?self $instance = null;

    public function __construct(private ?string $path = null)
    {
    }

    public static function instance(): self
    {
        return self::$instance ??= new self(Config::get('log.path'));
    }

    public static function setInstance(?self $logger): void
    {
        self::$instance = $logger;
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        if ($this->path === null || $this->path === '') {
            return; // logging disabled
        }

        $line = sprintf(
            "[%s] %s: %s%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : ''
        );

        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        // Logging must never break a request.
        @file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX);
    }
}
