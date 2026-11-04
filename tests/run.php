<?php

declare(strict_types=1);

/**
 * FlowForge Deterministic Test Runner
 * Executes all unit and integration test suites without third-party dependencies.
 */

$baseDir = dirname(__DIR__);

// Use built-in PSR-4 autoloader
require_once $baseDir . '/src/Core/Autoloader.php';
\FlowForge\Core\Autoloader::register();
\FlowForge\Core\Autoloader::addPrefix('FlowForge\\Tests\\', $baseDir . '/tests/');

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/RouterTest.php';
require_once __DIR__ . '/RequestTest.php';
require_once __DIR__ . '/ValidatorTest.php';
require_once __DIR__ . '/WorkflowEngineTest.php';
require_once __DIR__ . '/SecurityAndWorkflowIntegrationTest.php';

use FlowForge\Tests\TestCase;
use FlowForge\Tests\RouterTest;
use FlowForge\Tests\RequestTest;
use FlowForge\Tests\ValidatorTest;
use FlowForge\Tests\WorkflowEngineTest;
use FlowForge\Tests\SecurityAndWorkflowIntegrationTest;

echo "====================================================\n";
echo " FlowForge Microframework - Test Suite Execution\n";
echo "====================================================\n\n";

TestCase::reset();

$suites = [
    RouterTest::class,
    RequestTest::class,
    ValidatorTest::class,
    WorkflowEngineTest::class,
    SecurityAndWorkflowIntegrationTest::class,
];

$startTime = microtime(true);

foreach ($suites as $suite) {
    $suite::run();
    echo "\n";
}

$elapsed = round((microtime(true) - $startTime) * 1000, 2);
$assertions = TestCase::getAssertionCount();
$failures = TestCase::getFailures();

echo "====================================================\n";
if (empty($failures)) {
    echo "✓ ALL TESTS PASSED!\n";
    echo "Total Assertions: {$assertions} in {$elapsed} ms\n";
    echo "====================================================\n";
    exit(0);
} else {
    echo "✗ FAILURES ENCOUNTERED (" . count($failures) . " failed):\n";
    foreach ($failures as $f) {
        echo " - {$f}\n";
    }
    echo "Total Assertions: {$assertions} in {$elapsed} ms\n";
    echo "====================================================\n";
    exit(1);
}
