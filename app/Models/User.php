<?php

declare(strict_types=1);

namespace App\Models;

/**
 * User Entity Model.
 */
class User
{
    public int $id;
    public string $name;
    public string $email;
    public string $password_hash;
    public string $role;
    public string $created_at;

    public function __construct(array $attributes = [])
    {
        $this->id = (int)($attributes['id'] ?? 0);
        $this->name = (string)($attributes['name'] ?? '');
        $this->email = (string)($attributes['email'] ?? '');
        $this->password_hash = (string)($attributes['password_hash'] ?? '');
        $this->role = (string)($attributes['role'] ?? '');
        $this->created_at = (string)($attributes['created_at'] ?? '');
    }

    public function isReviewer(): bool
    {
        return $this->role === 'reviewer';
    }

    public function isSubmitter(): bool
    {
        return $this->role === 'submitter';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'created_at' => $this->created_at,
        ];
    }
}
