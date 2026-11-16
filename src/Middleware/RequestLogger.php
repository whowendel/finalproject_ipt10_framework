<?php

declare(strict_types=1);

namespace FlowForge\Middleware;

use FlowForge\Core\Request;
use FlowForge\Core\Response;
use FlowForge\Support\Logger;

/**
 * Global middleware: writes one access-log line per request
 * (method, path, status, duration) to storage/logs/app.log.
 */
final class RequestLogger implements MiddlewareInterface
{
    public function __construct(private ?Logger $logger = null)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        $ms = round((microtime(true) - $start) * 1000, 1);

        $status = $response->getStatusCode();
        $level = $status >= 500 ? 'error' : ($status >= 400 ? 'warning' : 'info');

        ($this->logger ?? Logger::instance())->log(
            $level,
            sprintf('%s %s -> %d (%s ms)', $request->getMethod(), $request->getPath(), $status, $ms)
        );

        return $response;
    }
}
