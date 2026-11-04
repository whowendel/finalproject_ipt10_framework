<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use FlowForge\Core\Database;
use PDO;

/**
 * UserRepository.
 * Handles database operations for User records with prepared statements.
 */
class UserRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connect();
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? new User($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $stmt->execute(['email' => trim($email)]);
        $row = $stmt->fetch();

        return $row ? new User($row) : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role, created_at) VALUES (:name, :email, :password_hash, :role, :created_at)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'role' => $data['role'],
            'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
