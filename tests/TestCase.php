<?php

declare(strict_types=1);

namespace FlowForge\Tests;

class TestCase
{
    private static int $assertions = 0;
    private static array $failures = [];

    public static function reset(): void
    {
        self::$assertions = 0;
        self::$failures = [];
    }

    public static function getAssertionCount(): int
    {
        return self::$assertions;
    }

    public static function getFailures(): array
    {
        return self::$failures;
    }

    public static function assert(bool $condition, string $message): void
    {
        self::$assertions++;
        if (!$condition) {
            self::$failures[] = $message;
            echo "  [FAIL] {$message}\n";
        } else {
            echo "  [PASS] {$message}\n";
        }
    }

    public static function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        $desc = $message !== '' ? $message : ("Expected " . json_encode($expected) . ", got " . json_encode($actual));
        self::assert($expected === $actual, $desc);
    }

    public static function assertTrue(bool $condition, string $message = 'Expected true'): void
    {
        self::assert($condition === true, $message);
    }

    public static function assertFalse(bool $condition, string $message = 'Expected false'): void
    {
        self::assert($condition === false, $message);
    }

    public static function assertNull(mixed $actual, string $message = 'Expected null'): void
    {
        self::assert($actual === null, $message);
    }

    public static function assertNotNull(mixed $actual, string $message = 'Expected not null'): void
    {
        self::assert($actual !== null, $message);
    }

    public static function assertContains(string $needle, string $haystack, string $message = ''): void
    {
        $desc = $message !== '' ? $message : ("Expected string to contain '{$needle}'");
        self::assert(str_contains($haystack, $needle), $desc);
    }
}
