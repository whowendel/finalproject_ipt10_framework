<?php

declare(strict_types=1);

namespace FlowForge\Core;

use PDO;
use PDOException;

/**
 * PDO Connection Wrapper for SQLite.
 * Manages singleton connection, enforces foreign keys, and provides transactional controls.
 */
class Database
{
    private static ?PDO $instance = null;
    private static ?string $customPath = null;

    /**
     * Set a custom path (useful for testing e.g. ':memory:').
     */
    public static function setPath(?string $path): void
    {
        self::$customPath = $path;
        self::$instance = null; // Reset existing instance
    }

    /**
     * Get or create PDO instance.
     */
    public static function connect(?string $dbPath = null): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        if ($dbPath === null) {
            $dbPath = self::$customPath;
        }

        if ($dbPath === null) {
            $config = require dirname(__DIR__, 2) . '/config/app.php';
            $dbPath = $config['database']['path'] ?? dirname(__DIR__, 2) . '/storage/database.sqlite';
        }

        if ($dbPath !== ':memory:') {
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }

        $dsn = 'sqlite:' . $dbPath;

        try {
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Enforce foreign key constraints in SQLite
            $pdo->exec('PRAGMA foreign_keys = ON;');
            $pdo->exec('PRAGMA journal_mode = WAL;');

            self::$instance = $pdo;
            return self::$instance;
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Reset connection instance.
     */
    public static function disconnect(): void
    {
        self::$instance = null;
    }
}
