<?php

declare(strict_types=1);

namespace FlowForge\Core;

use FlowForge\Middleware\MiddlewareInterface;

/**
 * Middleware pipeline ("onion" model).
 *
 * Each middleware receives the Request and a $next callable. It may return a
 * Response early (e.g. redirect to /login) or call $next($request) and then
 * inspect or decorate the Response on the way back out.
 */
final class Pipeline
{
    /**
     * @param array<int, MiddlewareInterface|callable> $middleware
     */
    public function __construct(private array $middleware = [])
    {
    }

    public function pipe(MiddlewareInterface|callable $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * @param callable(Request): Response $destination
     */
    public function process(Request $request, callable $destination): Response
    {
        $next = $destination;

        foreach (array_reverse($this->middleware) as $middleware) {
            $next = static function (Request $request) use ($middleware, $next): Response {
                if ($middleware instanceof MiddlewareInterface) {
                    return $middleware->handle($request, $next);
                }
                return $middleware($request, $next);
            };
        }

        return $next($request);
    }
}
