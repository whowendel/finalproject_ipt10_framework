<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use FlowForge\Core\Request;
use FlowForge\Core\Response;
use FlowForge\Core\View;
use FlowForge\Support\Validator;

/**
 * AuthController.
 * Handles authentication, login view rendering, credential validation,
 * session lifecycle, and logout.
 */
class AuthController
{
    private AuthService $authService;

    public function __construct(?AuthService $authService = null)
    {
        $this->authService = $authService ?? new AuthService();
    }

    public function showLogin(Request $request): Response
    {
        if ($this->authService->check()) {
            return Response::redirect('/requests');
        }

        return Response::html(View::render('auth/login', [
            'title' => 'Sign In - FlowForge',
            'old' => [],
            'errors' => [],
        ], 'layouts/main'));
    }

    public function login(Request $request): Response
    {
        $email = (string)$request->input('email', '');
        $password = (string)$request->input('password', '');

        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
        ], [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return Response::html(View::render('auth/login', [
                'title' => 'Sign In - FlowForge',
                'old' => ['email' => $email],
                'errors' => $validator->errors(),
            ], 'layouts/main'), 422);
        }

        if (!$this->authService->login($email, $password)) {
            return Response::html(View::render('auth/login', [
                'title' => 'Sign In - FlowForge',
                'old' => ['email' => $email],
                'errors' => [
                    'auth' => ['Invalid email or password. Please verify credentials.']
                ],
            ], 'layouts/main'), 401);
        }

        $_SESSION['_flash']['success'] = 'Welcome back! You are now logged in.';
        
        $user = $this->authService->user();
        if ($user && $user->isReviewer()) {
            return Response::redirect('/reviewer/queue');
        }

        return Response::redirect('/requests');
    }

    public function logout(Request $request): Response
    {
        $this->authService->logout();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['_flash']['info'] = 'You have been logged out successfully.';

        return Response::redirect('/login');
    }
}
