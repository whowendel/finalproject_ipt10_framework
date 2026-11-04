<?php

declare(strict_types=1);

namespace App\Repositories;

use FlowForge\Core\Database;
use PDO;

/**
 * ApprovalHistoryRepository.
 * Stores and retrieves audit trails of workflow status transitions.
 */
class ApprovalHistoryRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connect();
    }

    /**
     * Retrieve audit history in chronological order.
     */
    public function findByRequestId(int $requestId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT h.*, u.name AS actor_name, u.role AS actor_role, u.email AS actor_email
            FROM approval_history h
            JOIN users u ON h.actor_id = u.id
            WHERE h.request_id = :request_id
            ORDER BY h.created_at ASC, h.id ASC
        ');
        $stmt->execute(['request_id' => $requestId]);

        return $stmt->fetchAll();
    }

    public function create(
        int $requestId,
        int $actorId,
        string $action,
        ?string $remarks,
        ?string $fromStatus,
        string $toStatus,
        ?string $createdAt = null
    ): int {
        $stmt = $this->pdo->prepare('
            INSERT INTO approval_history (request_id, actor_id, action, remarks, from_status, to_status, created_at)
            VALUES (:request_id, :actor_id, :action, :remarks, :from_status, :to_status, :created_at)
        ');

        $stmt->execute([
            'request_id' => $requestId,
            'actor_id' => $actorId,
            'action' => $action,
            'remarks' => $remarks !== null && trim($remarks) !== '' ? trim($remarks) : null,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'created_at' => $createdAt ?? date('Y-m-d H:i:s'),
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
