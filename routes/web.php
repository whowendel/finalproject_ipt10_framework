<?php

declare(strict_types=1);

/**
 * Web Routes Configuration
 * @var \FlowForge\Core\Router $router
 */

use App\Controllers\ApprovalController;
use App\Controllers\AuthController;
use App\Controllers\RequestController;
use FlowForge\Core\Response;

// Root redirect
$router->get('/', function () {
    if (isset($_SESSION['user']['id'])) {
        return Response::redirect('/requests');
    }
    return Response::redirect('/login');
});

// Authentication
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// Activity Requests
$router->get('/requests', [RequestController::class, 'index']);
$router->get('/requests/create', [RequestController::class, 'create']);
$router->post('/requests', [RequestController::class, 'store']);
$router->get('/requests/{id}', [RequestController::class, 'show']);
$router->post('/requests/{id}/submit', [RequestController::class, 'submit']);

// Reviewer Workflow Actions
$router->get('/reviewer/queue', [ApprovalController::class, 'queue']);
$router->post('/requests/{id}/approve', [ApprovalController::class, 'approve']);
$router->post('/requests/{id}/reject', [ApprovalController::class, 'reject']);
