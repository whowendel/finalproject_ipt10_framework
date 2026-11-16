<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;
use FlowForge\Core\Request;
use FlowForge\Core\Response;
use FlowForge\Middleware\MiddlewareInterface;

/**
 * Route middleware "guest": pages such as /login are only for signed-out visitors.
 */
final class RedirectIfAuthenticated implements MiddlewareInterface
{
    public function __construct(private ?AuthService $auth = null)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $auth = $this->auth ?? new AuthService();

        if ($auth->check()) {
            return Response::redirect($auth->isReviewer() ? '/reviewer/queue' : '/requests');
        }

        return $next($request);
    }
}
