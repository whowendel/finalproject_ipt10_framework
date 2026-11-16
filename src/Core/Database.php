<?php

declare(strict_types=1);

namespace FlowForge\Core;

use PDO;
use PDOException;

/**
 * PDO connection manager.
 * Supports SQLite (default, zero setup) and MySQL/MariaDB, selected with
 * DB_DRIVER in .env. Keeps a single shared connection per request.
 */
class Database
{
    private static ?PDO $instance = null;
    private static ?string $customPath = null;

    /**
     * Force a specific SQLite path (useful for testing e.g. ':memory:').
     */
    public static function setPath(?string $path): void
    {
        self::$customPath = $path;
        self::$instance = null;
    }

    /**
     * The configured driver name: "sqlite" or "mysql".
     */
    public static function driver(): string
    {
        if (self::$customPath !== null) {
            return 'sqlite';
        }
        return strtolower((string)Config::get('database.driver', 'sqlite'));
    }

    /**
     * Get or create the shared PDO instance.
     * Passing $dbPath forces an SQLite connection to that file.
     */
    public static function connect(?string $dbPath = null): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $dbPath ??= self::$customPath;
        $driver = $dbPath !== null ? 'sqlite' : self::driver();

        try {
            self::$instance = $driver === 'mysql'
                ? self::connectMysql()
                : self::connectSqlite($dbPath ?? (string)Config::get('database.path'));

            return self::$instance;
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), 500, $e);
        }
    }

    private static function options(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }

    private static function connectSqlite(string $path): PDO
    {
        if ($path !== ':memory:') {
            // Relative paths (e.g. DB_PATH=storage/app.sqlite) are relative to the project root.
            if (!preg_match('#^([a-zA-Z]:[\\\\/]|/|\\\\)#', $path)) {
                $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $path;
            }
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }

        $pdo = new PDO('sqlite:' . $path, null, null, self::options());

        // SQLite needs foreign keys switched on per connection.
        $pdo->exec('PRAGMA foreign_keys = ON;');
        // Wait instead of failing immediately when another request holds a lock.
        $pdo->exec('PRAGMA busy_timeout = 5000;');
        // Default rollback journal: WAL's extra -wal/-shm files misbehave inside
        // synced folders such as OneDrive or Dropbox.
        $pdo->exec('PRAGMA journal_mode = DELETE;');

        return $pdo;
    }

    private static function connectMysql(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            Config::get('database.host', '127.0.0.1'),
            (int)Config::get('database.port', 3306),
            Config::get('database.database', 'flowforge')
        );

        return new PDO(
            $dsn,
            (string)Config::get('database.username', 'root'),
            (string)Config::get('database.password', ''),
            self::options()
        );
    }

    /**
     * Reset the connection instance.
     */
    public static function disconnect(): void
    {
        self::$instance = null;
    }
}
