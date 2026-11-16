<?php

declare(strict_types=1);

/**
 * JSON API Routes Configuration
 * @var \FlowForge\Core\Router $router
 */

use App\Controllers\ApprovalController;
use App\Controllers\HolidayController;
use App\Controllers\RequestController;

$router->group(['prefix' => '/api'], function ($router) {
    // Public health check
    $router->get('/health', [RequestController::class, 'apiHealth']);

    $router->group(['middleware' => ['auth']], function ($router) {
        // Requests resource
        $router->get('/requests', [RequestController::class, 'apiIndex']);
        $router->get('/requests/{id}', [RequestController::class, 'apiShow']);
        $router->post('/requests', [RequestController::class, 'apiStore'])->middleware('role:submitter');
        $router->put('/requests/{id}', [RequestController::class, 'apiUpdate'])->middleware('role:submitter');
        $router->patch('/requests/{id}', [RequestController::class, 'apiUpdate'])->middleware('role:submitter');
        $router->delete('/requests/{id}', [RequestController::class, 'apiDelete'])->middleware('role:submitter');

        // Workflow transitions
        $router->post('/requests/{id}/submit', [RequestController::class, 'apiSubmit'])->middleware('role:submitter');
        $router->post('/requests/{id}/approve', [ApprovalController::class, 'apiApprove'])->middleware('role:reviewer');
        $router->post('/requests/{id}/reject', [ApprovalController::class, 'apiReject'])->middleware('role:reviewer');

        // External API proxy (Nager.Date public holidays)
        $router->get('/holidays', [HolidayController::class, 'index']);
        $router->get('/holidays/check', [HolidayController::class, 'check']);
    });
});
