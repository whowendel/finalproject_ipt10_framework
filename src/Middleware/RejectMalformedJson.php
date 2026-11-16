<?php

declare(strict_types=1);

namespace FlowForge\Middleware;

use FlowForge\Core\Request;
use FlowForge\Core\Response;

/**
 * Global middleware: stops requests whose JSON body cannot be parsed
 * before they reach a controller (400 Bad Request).
 */
final class RejectMalformedJson implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if ($request->hasJsonError()) {
            return Response::json([
                'message' => 'Malformed JSON payload',
                'error' => $request->getJsonErrorMessage(),
            ], 400);
        }

        return $next($request);
    }
}
