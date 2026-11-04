<?php

declare(strict_types=1);

/**
 * JSON API Routes Configuration
 * @var \FlowForge\Core\Router $router
 */

use App\Controllers\ApprovalController;
use App\Controllers\RequestController;

// Public Health
$router->get('/api/health', [RequestController::class, 'apiHealth']);

// Requests Resource
$router->get('/api/requests', [RequestController::class, 'apiIndex']);
$router->get('/api/requests/{id}', [RequestController::class, 'apiShow']);
$router->post('/api/requests', [RequestController::class, 'apiStore']);
$router->put('/api/requests/{id}', [RequestController::class, 'apiUpdate']);
$router->patch('/api/requests/{id}', [RequestController::class, 'apiUpdate']);
$router->delete('/api/requests/{id}', [RequestController::class, 'apiDelete']);

// Workflow Transitions
$router->post('/api/requests/{id}/submit', [RequestController::class, 'apiSubmit']);
$router->post('/api/requests/{id}/approve', [ApprovalController::class, 'apiApprove']);
$router->post('/api/requests/{id}/reject', [ApprovalController::class, 'apiReject']);
