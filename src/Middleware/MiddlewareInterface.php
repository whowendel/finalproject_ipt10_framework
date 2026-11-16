<?php

declare(strict_types=1);

namespace FlowForge\Middleware;

use FlowForge\Core\Request;
use FlowForge\Core\Response;

/**
 * Contract for request filters that wrap route handlers.
 */
interface MiddlewareInterface
{
    /**
     * @param callable(Request): Response $next
     */
    public function handle(Request $request, callable $next): Response;
}
