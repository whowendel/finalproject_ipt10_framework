<?php

declare(strict_types=1);

/**
 * Web Routes Configuration
 * @var \FlowForge\Core\Router $router
 *
 * Middleware aliases (registered in Application::loadRoutes):
 *   auth          - must be signed in
 *   guest         - must be signed out
 *   role:<name>   - must have the given role (submitter | reviewer)
 */

use App\Controllers\ApprovalController;
use App\Controllers\AuthController;
use App\Controllers\EventController;
use App\Controllers\RequestController;
use FlowForge\Core\Response;

// Root redirect
$router->get('/', function () {
    if (isset($_SESSION['user']['id'])) {
        return Response::redirect('/requests');
    }
    return Response::redirect('/events');
});

// Public pages
$router->get('/events', [EventController::class, 'index']);

// Authentication
$router->get('/login', [AuthController::class, 'showLogin'])->middleware('guest');
$router->post('/login', [AuthController::class, 'login'])->middleware('guest');
$router->post('/logout', [AuthController::class, 'logout'])->middleware('auth');

// Everything below requires a signed-in user
$router->group(['middleware' => ['auth']], function ($router) {
    // Activity requests (content)
    $router->get('/requests', [RequestController::class, 'index']);
    $router->get('/requests/create', [RequestController::class, 'create'])->middleware('role:submitter');
    $router->post('/requests', [RequestController::class, 'store'])->middleware('role:submitter');
    $router->get('/requests/{id}', [RequestController::class, 'show']);
    $router->get('/requests/{id}/edit', [RequestController::class, 'edit'])->middleware('role:submitter');
    $router->post('/requests/{id}/update', [RequestController::class, 'update'])->middleware('role:submitter');
    $router->post('/requests/{id}/delete', [RequestController::class, 'destroy'])->middleware('role:submitter');
    $router->post('/requests/{id}/submit', [RequestController::class, 'submit'])->middleware('role:submitter');

    // Reviewer workflow actions
    $router->group(['middleware' => ['role:reviewer']], function ($router) {
        $router->get('/reviewer/queue', [ApprovalController::class, 'queue']);
        $router->post('/requests/{id}/approve', [ApprovalController::class, 'approve']);
        $router->post('/requests/{id}/reject', [ApprovalController::class, 'reject']);
    });
});
