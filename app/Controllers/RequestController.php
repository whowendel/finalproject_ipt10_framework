<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ApprovalHistoryRepository;
use App\Repositories\RequestRepository;
use App\Services\AuthService;
use App\Services\WorkflowService;
use FlowForge\Core\Request;
use FlowForge\Core\Response;
use FlowForge\Core\View;
use FlowForge\Support\Validator;
use FlowForge\Workflow\WorkflowEngine;

/**
 * RequestController.
 * Coordinates HTML and API CRUD operations for activity requests.
 */
class RequestController
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

    /* -------------------------------------------------------------
     * HTML Web Endpoints
     * ------------------------------------------------------------- */

    public function index(Request $request): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            return Response::redirect('/login');
        }

        if ($user->isReviewer()) {
            $requests = $this->requestRepo->findAll();
        } else {
            $requests = $this->requestRepo->findByUserId($user->id);
        }

        return Response::html(View::render('requests/index', [
            'title' => 'Activity Requests - FlowForge',
            'requests' => $requests,
            'user' => $user,
        ], 'layouts/main'));
    }

    public function create(Request $request): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            return Response::redirect('/login');
        }

        if (!$user->isSubmitter()) {
            $_SESSION['_flash']['error'] = 'Only student submitters can create activity requests.';
            return Response::redirect('/requests');
        }

        return Response::html(View::render('requests/create', [
            'title' => 'New Activity Request - FlowForge',
            'old' => [],
            'errors' => [],
        ], 'layouts/main'));
    }

    public function store(Request $request): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            return Response::redirect('/login');
        }

        if (!$user->isSubmitter()) {
            $_SESSION['_flash']['error'] = 'Only student submitters can create activity requests.';
            return Response::redirect('/requests');
        }

        $input = [
            'title' => trim((string)$request->input('title', '')),
            'description' => trim((string)$request->input('description', '')),
            'event_date' => trim((string)$request->input('event_date', '')),
            'venue' => trim((string)$request->input('venue', '')),
        ];

        $validator = Validator::make($input, [
            'title' => 'required|max:200',
            'description' => 'required|max:2000',
            'event_date' => 'required|date',
            'venue' => 'required|max:200',
        ]);

        if ($validator->fails()) {
            return Response::html(View::render('requests/create', [
                'title' => 'New Activity Request - FlowForge',
                'old' => $input,
                'errors' => $validator->errors(),
            ], 'layouts/main'), 422);
        }

        // Mass assignment protection: allowlist fields
        $newId = $this->requestRepo->create([
            'user_id' => $user->id,
            'title' => $input['title'],
            'description' => $input['description'],
            'event_date' => $input['event_date'],
            'venue' => $input['venue'],
            'status' => WorkflowEngine::STATUS_DRAFT,
        ]);

        $_SESSION['_flash']['success'] = 'Activity request draft created successfully!';
        return Response::redirect('/requests/' . $newId);
    }

    public function show(Request $request): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            return Response::redirect('/login');
        }

        $id = (int)$request->param('id');
        $activityRequest = $this->requestRepo->findById($id);

        if (!$activityRequest) {
            return Response::html(View::render('errors/404', [
                'message' => "Request #{$id} was not found."
            ], 'layouts/main'), 404);
        }

        // Role authorization check
        if ($user->isSubmitter() && $activityRequest->user_id !== $user->id) {
            return Response::html(View::render('errors/403', [
                'message' => 'You do not have permission to view this activity request.'
            ], 'layouts/main'), 403);
        }

        $history = $this->historyRepo->findByRequestId($id);

        return Response::html(View::render('requests/show', [
            'title' => "Request #{$id} - " . View::e($activityRequest->title),
            'request' => $activityRequest,
            'history' => $history,
            'user' => $user,
        ], 'layouts/main'));
    }

    public function submit(Request $request): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            return Response::redirect('/login');
        }

        $id = (int)$request->param('id');
        $remarks = $request->input('remarks');

        $result = $this->workflowService->transition($id, WorkflowEngine::ACTION_SUBMIT, $user, $remarks);

        if (!$result['success']) {
            $_SESSION['_flash']['error'] = $result['error'];
            return Response::redirect('/requests/' . $id);
        }

        $_SESSION['_flash']['success'] = 'Request submitted for faculty review!';
        return Response::redirect('/requests/' . $id);
    }

    /* -------------------------------------------------------------
     * JSON API Endpoints
     * ------------------------------------------------------------- */

    public function apiHealth(Request $request): Response
    {
        return Response::json([
            'status' => 'healthy',
            'framework' => 'FlowForge',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    public function apiIndex(Request $request): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            return Response::json(['message' => 'Unauthenticated'], 401);
        }

        if ($user->isReviewer()) {
            $requests = $this->requestRepo->findAll();
        } else {
            $requests = $this->requestRepo->findByUserId($user->id);
        }

        $data = array_map(fn($r) => $r->toArray(), $requests);
        return Response::json(['data' => $data], 200);
    }

    public function apiShow(Request $request): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            return Response::json(['message' => 'Unauthenticated'], 401);
        }

        $id = (int)$request->param('id');
        $item = $this->requestRepo->findById($id);

        if (!$item) {
            return Response::json(['message' => 'Request not found'], 404);
        }

        if ($user->isSubmitter() && $item->user_id !== $user->id) {
            return Response::json(['message' => 'Forbidden'], 403);
        }

        $history = $this->historyRepo->findByRequestId($id);

        return Response::json([
            'data' => $item->toArray(),
            'history' => $history,
        ], 200);
    }

    public function apiStore(Request $request): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            return Response::json(['message' => 'Unauthenticated'], 401);
        }

        if (!$user->isSubmitter()) {
            return Response::json(['message' => 'Forbidden: Only submitters can create requests'], 403);
        }

        $input = [
            'title' => trim((string)$request->input('title', '')),
            'description' => trim((string)$request->input('description', '')),
            'event_date' => trim((string)$request->input('event_date', '')),
            'venue' => trim((string)$request->input('venue', '')),
        ];

        $validator = Validator::make($input, [
            'title' => 'required|max:200',
            'description' => 'required|max:2000',
            'event_date' => 'required|date',
            'venue' => 'required|max:200',
        ]);

        if ($validator->fails()) {
            return Response::json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $newId = $this->requestRepo->create([
            'user_id' => $user->id,
            'title' => $input['title'],
            'description' => $input['description'],
            'event_date' => $input['event_date'],
            'venue' => $input['venue'],
            'status' => WorkflowEngine::STATUS_DRAFT,
        ]);

        $created = $this->requestRepo->findById($newId);

        return Response::json([
            'message' => 'Draft request created successfully',
            'data' => $created ? $created->toArray() : ['id' => $newId, 'status' => 'draft'],
        ], 201);
    }

    public function apiSubmit(Request $request): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            return Response::json(['message' => 'Unauthenticated'], 401);
        }

        $id = (int)$request->param('id');
        $remarks = $request->input('remarks');

        $result = $this->workflowService->transition($id, WorkflowEngine::ACTION_SUBMIT, $user, $remarks);

        if (!$result['success']) {
            return Response::json([
                'message' => $result['error'],
            ], $result['status_code']);
        }

        return Response::json([
            'message' => 'Request submitted successfully',
            'data' => $result['request']->toArray(),
        ], 200);
    }

    public function apiUpdate(Request $request): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            return Response::json(['message' => 'Unauthenticated'], 401);
        }

        $id = (int)$request->param('id');
        $item = $this->requestRepo->findById($id);

        if (!$item) {
            return Response::json(['message' => 'Request not found'], 404);
        }

        if ($item->user_id !== $user->id) {
            return Response::json(['message' => 'Forbidden'], 403);
        }

        if ($item->status !== WorkflowEngine::STATUS_DRAFT) {
            return Response::json(['message' => 'Only draft requests can be edited'], 422);
        }

        $input = [
            'title' => trim((string)$request->input('title', $item->title)),
            'description' => trim((string)$request->input('description', $item->description)),
            'event_date' => trim((string)$request->input('event_date', $item->event_date)),
            'venue' => trim((string)$request->input('venue', $item->venue)),
        ];

        $validator = Validator::make($input, [
            'title' => 'required|max:200',
            'description' => 'required|max:2000',
            'event_date' => 'required|date',
            'venue' => 'required|max:200',
        ]);

        if ($validator->fails()) {
            return Response::json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->requestRepo->updateDraft($id, $input);
        $updated = $this->requestRepo->findById($id);

        return Response::json([
            'message' => 'Draft request updated successfully',
            'data' => $updated ? $updated->toArray() : [],
        ], 200);
    }

    public function apiDelete(Request $request): Response
    {
        $user = $this->authService->user();
        if (!$user) {
            return Response::json(['message' => 'Unauthenticated'], 401);
        }

        $id = (int)$request->param('id');
        $item = $this->requestRepo->findById($id);

        if (!$item) {
            return Response::json(['message' => 'Request not found'], 404);
        }

        if ($item->user_id !== $user->id) {
            return Response::json(['message' => 'Forbidden'], 403);
        }

        if ($item->status !== WorkflowEngine::STATUS_DRAFT) {
            return Response::json(['message' => 'Approved, rejected, or submitted requests cannot be deleted'], 422);
        }

        $this->requestRepo->deleteDraft($id);

        return Response::json([
            'message' => 'Draft request deleted successfully',
        ], 200);
    }
}
