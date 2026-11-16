<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;
use FlowForge\Core\Request;
use FlowForge\Core\Response;
use FlowForge\Middleware\MiddlewareInterface;

/**
 * Route middleware "auth": only signed-in users may continue.
 * Browsers are redirected to /login; API clients receive 401 JSON.
 */
final class Authenticate implements MiddlewareInterface
{
    public function __construct(private ?AuthService $auth = null)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $auth = $this->auth ?? new AuthService();

        if (!$auth->check()) {
            if ($request->expectsJson()) {
                return Response::json(['message' => 'Unauthenticated'], 401);
            }
            $_SESSION['_flash']['info'] = 'Please sign in to continue.';
            return Response::redirect('/login');
        }

        return $next($request);
    }
}
