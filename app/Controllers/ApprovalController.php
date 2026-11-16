<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ApprovalHistoryRepository;
use App\Repositories\RequestRepository;
use App\Services\AuthService;
use App\Services\HolidayService;
use App\Services\WorkflowService;
use FlowForge\Core\Request;
use FlowForge\Core\Response;
use FlowForge\Core\View;
use FlowForge\Workflow\WorkflowEngine;

/**
 * ApprovalController.
 * Coordinates reviewer queue inspection, approvals, and rejections.
 */
class ApprovalController
{
    private RequestRepository $requestRepo;
    private ApprovalHistoryRepository $historyRepo;
    private AuthService $authService;
    private WorkflowService $workflowService;

    public function __construct(
        ?RequestRepository $requestRepo = null,
        ?ApprovalHistoryRepository $historyRepo = null,
        ?AuthService $authService = null,
        ?WorkflowService $workflowService = null
    ) {
        $this->requestRepo = $requestRepo ?? new RequestRepository();
        $this->historyRepo = $historyRepo ?? new ApprovalHistoryRepository();
        $this->authService = $authService ?? new AuthService();
        $this->workflowService = $workflowService ?? new WorkflowService($this->requestRepo, $this->historyRepo);
    }

    public function queue(Request $request): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            return Response::redirect('/login');
        }

        if (!$user->isReviewer()) {
            return Response::html(View::render('errors/403', [
                'message' => 'Access denied: The reviewer queue is restricted to authorized faculty reviewers.'
            ], 'layouts/main'), 403);
        }

        $pendingRequests = $this->requestRepo->findSubmittedQueue();

        return Response::html(View::render('reviewer/queue', [
            'title' => 'Reviewer Queue - FlowForge',
            'requests' => $pendingRequests,
            'user' => $user,
            'holidayNames' => (new HolidayService())->namesForDates(array_map(fn($r) => $r->event_date, $pendingRequests)),
        ], 'layouts/main'));
    }

    public function approve(Request $request): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            return Response::redirect('/login');
        }

        $id = (int)$request->param('id');
        $remarks = $request->input('remarks');

        $result = $this->workflowService->transition($id, WorkflowEngine::ACTION_APPROVE, $user, $remarks);

        if (!$result['success']) {
            $_SESSION['_flash']['error'] = $result['error'];
            return Response::redirect('/requests/' . $id);
        }

        $_SESSION['_flash']['success'] = "Request #{$id} has been approved successfully!";
        return Response::redirect('/requests/' . $id);
    }

    public function reject(Request $request): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            return Response::redirect('/login');
        }

        $id = (int)$request->param('id');
        $remarks = (string)$request->input('remarks', '');

        $result = $this->workflowService->transition($id, WorkflowEngine::ACTION_REJECT, $user, $remarks);

        if (!$result['success']) {
            $_SESSION['_flash']['error'] = $result['error'];
            return Response::redirect('/requests/' . $id);
        }

        $_SESSION['_flash']['warning'] = "Request #{$id} has been rejected.";
        return Response::redirect('/requests/' . $id);
    }

    /* -------------------------------------------------------------
     * JSON API Endpoints
     * ------------------------------------------------------------- */

    public function apiApprove(Request $request): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            return Response::json(['message' => 'Unauthenticated'], 401);
        }

        $id = (int)$request->param('id');
        $remarks = $request->input('remarks');

        $result = $this->workflowService->transition($id, WorkflowEngine::ACTION_APPROVE, $user, $remarks);

        if (!$result['success']) {
            return Response::json([
                'message' => $result['error'],
            ], $result['status_code']);
        }

        return Response::json([
            'message' => 'Request approved successfully',
            'data' => $result['request']->toArray(),
        ], 200);
    }

    public function apiReject(Request $request): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            return Response::json(['message' => 'Unauthenticated'], 401);
        }

        $id = (int)$request->param('id');
        $remarks = $request->input('remarks');

        $result = $this->workflowService->transition($id, WorkflowEngine::ACTION_REJECT, $user, $remarks);

        if (!$result['success']) {
            return Response::json([
                'message' => $result['error'],
            ], $result['status_code']);
        }

        return Response::json([
            'message' => 'Request rejected',
            'data' => $result['request']->toArray(),
        ], 200);
    }
}
