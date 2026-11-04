<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ActivityRequest;
use FlowForge\Core\Database;
use PDO;

/**
 * RequestRepository.
 * Manages database persistence and queries for Activity Requests.
 */
class RequestRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connect();
    }

    public function findById(int $id): ?ActivityRequest
    {
        $stmt = $this->pdo->prepare('
            SELECT r.*, u.name AS submitter_name, u.email AS submitter_email
            FROM activity_requests r
            JOIN users u ON r.user_id = u.id
            WHERE r.id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? new ActivityRequest($row) : null;
    }

    /**
     * @return ActivityRequest[]
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('
            SELECT r.*, u.name AS submitter_name, u.email AS submitter_email
            FROM activity_requests r
            JOIN users u ON r.user_id = u.id
            ORDER BY r.created_at DESC
        ');

        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = new ActivityRequest($row);
        }
        return $results;
    }

    /**
     * @return ActivityRequest[]
     */
    public function findByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT r.*, u.name AS submitter_name, u.email AS submitter_email
            FROM activity_requests r
            JOIN users u ON r.user_id = u.id
            WHERE r.user_id = :user_id
            ORDER BY r.created_at DESC
        ');
        $stmt->execute(['user_id' => $userId]);

        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = new ActivityRequest($row);
        }
        return $results;
    }

    /**
     * Reviewer queue: all requests in 'submitted' status.
     * @return ActivityRequest[]
     */
    public function findSubmittedQueue(): array
    {
        $stmt = $this->pdo->query('
            SELECT r.*, u.name AS submitter_name, u.email AS submitter_email
            FROM activity_requests r
            JOIN users u ON r.user_id = u.id
            WHERE r.status = \'submitted\'
            ORDER BY r.updated_at ASC
        ');

        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = new ActivityRequest($row);
        }
        return $results;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO activity_requests (user_id, title, description, event_date, venue, status, created_at, updated_at)
            VALUES (:user_id, :title, :description, :event_date, :venue, :status, :created_at, :updated_at)
        ');

        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'event_date' => $data['event_date'],
            'venue' => $data['venue'],
            'status' => $data['status'] ?? 'draft',
            'created_at' => $data['created_at'] ?? $now,
            'updated_at' => $data['updated_at'] ?? $now,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status, ?string $updatedAt = null): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE activity_requests
            SET status = :status, updated_at = :updated_at
            WHERE id = :id
        ');

        return $stmt->execute([
            'id' => $id,
            'status' => $status,
            'updated_at' => $updatedAt ?? date('Y-m-d H:i:s'),
        ]);
    }

    public function updateDraft(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE activity_requests
            SET title = :title, description = :description, event_date = :event_date, venue = :venue, updated_at = :updated_at
            WHERE id = :id AND status = \'draft\'
        ');

        return $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description'],
            'event_date' => $data['event_date'],
            'venue' => $data['venue'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function deleteDraft(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM activity_requests WHERE id = :id AND status = \'draft\'');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
