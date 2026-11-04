<?php

declare(strict_types=1);

/**
 * FlowForge Front Controller
 * Boots the application and dispatches the incoming HTTP request.
 */

$baseDir = dirname(__DIR__);

// Load Composer autoloader if present; otherwise use built-in PSR-4 autoloader
if (file_exists($baseDir . '/vendor/autoload.php')) {
    require_once $baseDir . '/vendor/autoload.php';
} else {
    require_once $baseDir . '/src/Core/Autoloader.php';
    \FlowForge\Core\Autoloader::register();
}

// Instantiate and run FlowForge Application
$app = new \FlowForge\Core\Application($baseDir);
$app->run();
