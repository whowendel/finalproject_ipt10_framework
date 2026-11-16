<?php

declare(strict_types=1);

namespace FlowForge\Middleware;

use FlowForge\Core\HttpException;
use FlowForge\Core\Request;
use FlowForge\Core\Response;
use FlowForge\Support\Csrf;

/**
 * Global middleware: every state-changing browser request (POST/PUT/PATCH/DELETE)
 * must carry the session's CSRF token in the "_csrf" field.
 *
 * JSON API routes are excluded; they rely on the SameSite=Lax session cookie,
 * which browsers do not send on cross-site POSTs.
 */
final class VerifyCsrfToken implements MiddlewareInterface
{
    private const UNSAFE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, callable $next): Response
    {
        if (!$request->isApiRoute() && in_array($request->getMethod(), self::UNSAFE_METHODS, true)) {
            $token = $request->input('_csrf');
            if (!is_string($token) || !Csrf::verify($token)) {
                throw new HttpException(419, 'Page expired or invalid CSRF token. Please refresh and try again.');
            }
        }

        return $next($request);
    }
}
