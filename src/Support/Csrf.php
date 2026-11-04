<?php

declare(strict_types=1);

namespace FlowForge\Support;

/**
 * CSRF Protection Manager.
 * Generates and validates session-bound anti-forgery tokens.
 */
class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }

    public static function verify(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;

        if (!$sessionToken || !$token) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}
