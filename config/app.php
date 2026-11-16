<?php

declare(strict_types=1);

use FlowForge\Core\Env;

/*
 * FlowForge configuration.
 * Values come from the .env file in the project root (see .env.example);
 * the second argument of Env::get() is the default used when a key is missing.
 */

$basePath = dirname(__DIR__);
Env::load($basePath);

$storage = $basePath . DIRECTORY_SEPARATOR . 'storage';

return [
    'app_name' => Env::get('APP_NAME', 'FlowForge Activity System'),
    'env' => Env::get('APP_ENV', 'development'),
    'debug' => (bool)Env::get('APP_DEBUG', false),
    'timezone' => Env::get('APP_TIMEZONE', 'Asia/Manila'),

    // Public base URL, e.g. http://localhost/IPT10-Final-Project. Only the path part
    // is used for links; leave the path empty to auto-detect the install folder.
    'url' => Env::get('APP_URL', ''),

    'database' => [
        'driver' => Env::get('DB_DRIVER', 'sqlite'),            // sqlite | mysql
        // SQLite
        'path' => Env::get('DB_PATH', $storage . DIRECTORY_SEPARATOR . 'database.sqlite'),
        // MySQL / MariaDB
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => (int)Env::get('DB_PORT', 3306),
        'database' => Env::get('DB_DATABASE', 'flowforge'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
    ],

    'session' => [
        'name' => Env::get('SESSION_NAME', 'flowforge_session'),
        'lifetime' => (int)Env::get('SESSION_LIFETIME', 7200),
    ],

    'log' => [
        // Set LOG_PATH to "false" to disable file logging.
        'path' => Env::get('LOG_PATH', $storage . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app.log'),
    ],

    'cache' => [
        'path' => $storage . DIRECTORY_SEPARATOR . 'cache',
    ],

    // External API: Nager.Date public holidays (https://date.nager.at)
    'holidays' => [
        'base_url' => Env::get('HOLIDAY_API_URL', 'https://date.nager.at/api/v3'),
        'country' => strtoupper((string)Env::get('HOLIDAY_COUNTRY', 'PH')),
        'cache_ttl' => (int)Env::get('HOLIDAY_CACHE_TTL', 86400),
        'timeout' => (int)Env::get('HOLIDAY_API_TIMEOUT', 5),
    ],
];
