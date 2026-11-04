<?php

declare(strict_types=1);

return [
    'app_name' => 'FlowForge Activity System',
    'env' => 'development',
    'debug' => true,
    'database' => [
        'path' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'database.sqlite',
    ],
    'session' => [
        'name' => 'flowforge_session',
        'lifetime' => 7200,
    ],
];
