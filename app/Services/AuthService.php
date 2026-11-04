<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;

/**
 * Authentication and Session Management Service.
 * Implements secure login with password_verify(), session fixation protection (session_regenerate_id),
 * and role-based checks.
 */
class AuthService
{
    private UserRepository $userRepository;

    public function __construct(?UserRepository $userRepository = null)
    {
        $this->userRepository = $userRepository ?? new UserRepository();
    }

    public function login(string $email, string $password): bool
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user->password_hash)) {
            return false;
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        // Prevent session fixation attacks
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];

        return true;
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public function check(): bool
    {
        return isset($_SESSION['user']['id']);
    }

    public function user(): ?User
    {
        if (!$this->check()) {
            return null;
        }

        return new User($_SESSION['user']);
    }

    public function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public function role(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
    }

    public function isReviewer(): bool
    {
        return $this->role() === 'reviewer';
    }

    public function isSubmitter(): bool
    {
        return $this->role() === 'submitter';
    }

    public function setUser(User $user): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $_SESSION['user'] = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }
}
