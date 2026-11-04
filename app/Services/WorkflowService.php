<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityRequest;
use App\Models\User;
use App\Repositories\ApprovalHistoryRepository;
use App\Repositories\RequestRepository;
use FlowForge\Core\Database;
use FlowForge\Workflow\WorkflowEngine;
use PDO;

/**
 * WorkflowService.
 * Coordinates request state transitions, authorization, atomic database transactions,
 * and audit history logging. Shared between Web and API controllers.
 */
class WorkflowService
{
    private RequestRepository $requestRepo;
    private ApprovalHistoryRepository $historyRepo;
    private WorkflowEngine $workflowEngine;
    private PDO $pdo;

    public function __construct(
        ?RequestRepository $requestRepo = null,
        ?ApprovalHistoryRepository $historyRepo = null,
        ?WorkflowEngine $workflowEngine = null,
        ?PDO $pdo = null
    ) {
        $this->requestRepo = $requestRepo ?? new RequestRepository();
        $this->historyRepo = $historyRepo ?? new ApprovalHistoryRepository();
        $this->workflowEngine = $workflowEngine ?? new WorkflowEngine();
        $this->pdo = $pdo ?? Database::connect();
    }

    /**
     * Executes an end-to-end atomic workflow transition.
     *
     * @param int $requestId Request ID to transition
     * @param string $action 'submit', 'approve', or 'reject'
     * @param User $actor Authenticated user performing the action
     * @param string|null $remarks Required for reject, optional for approve/submit
     * @return array{success: bool, request: ?ActivityRequest, error: ?string, status_code: int}
     */
    public function transition(int $requestId, string $action, User $actor, ?string $remarks = null): array
    {
        // 1. Load the request and confirm that it exists
        $request = $this->requestRepo->findById($requestId);
        if ($request === null) {
            return [
                'success' => false,
                'request' => null,
                'error' => "Request #{$requestId} not found.",
                'status_code' => 404,
            ];
        }

        // Additional validation: rejection requires remarks
        if ($action === WorkflowEngine::ACTION_REJECT && ($remarks === null || trim($remarks) === '')) {
            return [
                'success' => false,
                'request' => $request,
                'error' => 'A rejection remark is required when rejecting a request.',
                'status_code' => 422,
            ];
        }

        // 2 & 3. Verify logged-in user's role, ownership, and WorkflowEngine rules
        $isOwner = ($request->user_id === $actor->id);
        $decision = $this->workflowEngine->validateTransition(
            $request->status,
            $action,
            $actor->role,
            $isOwner
        );

        if (!$decision['allowed']) {
            // Determine appropriate status code (403 for role/ownership, 422/400 for invalid status)
            $statusCode = str_starts_with($decision['error'] ?? '', 'Unauthorized') ? 403 : 422;
            return [
                'success' => false,
                'request' => $request,
                'error' => $decision['error'],
                'status_code' => $statusCode,
            ];
        }

        $fromStatus = $request->status;
        $nextStatus = $decision['next_status'];
        $now = date('Y-m-d H:i:s');

        // 4. Begin database transaction
        $this->pdo->beginTransaction();

        try {
            // 5. Update request status and updated_at timestamp
            $this->requestRepo->updateStatus($requestId, $nextStatus, $now);

            // 6. Insert approval_history row
            $this->historyRepo->create(
                $requestId,
                $actor->id,
                $action,
                $remarks,
                $fromStatus,
                $nextStatus,
                $now
            );

            // 7. Commit transaction
            $this->pdo->commit();

            // 8. Return updated request
            $updatedRequest = $this->requestRepo->findById($requestId);
            return [
                'success' => true,
                'request' => $updatedRequest,
                'error' => null,
                'status_code' => 200,
            ];
        } catch (\Throwable $e) {
            // Roll back if any write fails
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'request' => $request,
                'error' => 'Database transaction failed during workflow transition: ' . $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }
}
