<?php

declare(strict_types=1);

namespace FlowForge\Core;

use FlowForge\Middleware\MiddlewareInterface;

/**
 * Custom Method/Path Matcher Router.
 * Supports static routes, parameterized dynamic routes ({id}), route groups,
 * per-route middleware, and distinguishes 404 Not Found from 405 Method Not Allowed.
 *
 *   $router->group(['middleware' => ['auth']], function (Router $router) {
 *       $router->get('/requests', [RequestController::class, 'index']);
 *       $router->get('/reviewer/queue', [ApprovalController::class, 'queue'])->middleware('role:reviewer');
 *   });
 */
class Router
{
    /**
     * @var array<int, array{method: string, path: string, regex: string, paramNames: array<string>, handler: callable|array, middleware: array}>
     */
    private array $routes = [];

    /** @var array<int, array{prefix: string, middleware: array}> */
    private array $groupStack = [];

    /** @var array<string, string|callable> alias => middleware class name or factory */
    private array $middlewareAliases = [];

    /** @var null|callable(int, Request, array): Response */
    private $fallback = null;

    public function get(string $path, callable|array $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function patch(string $path, callable|array $handler): self
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function addRoute(string $method, string $path, callable|array $handler): self
    {
        $prefix = '';
        $middleware = [];
        foreach ($this->groupStack as $group) {
            $prefix .= $group['prefix'];
            $middleware = array_merge($middleware, $group['middleware']);
        }

        $normalizedPath = $this->normalizePath($prefix . '/' . ltrim($path, '/'));
        [$regex, $paramNames] = $this->compileRoute($normalizedPath);

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $normalizedPath,
            'regex' => $regex,
            'paramNames' => $paramNames,
            'handler' => $handler,
            'middleware' => $middleware,
        ];

        return $this;
    }

    /**
     * Attach middleware to the most recently added route.
     */
    public function middleware(string|array|MiddlewareInterface|\Closure $middleware): self
    {
        $last = array_key_last($this->routes);
        if ($last === null) {
            throw new \LogicException('middleware() must be called after defining a route.');
        }

        $list = is_array($middleware) ? $middleware : [$middleware];
        $this->routes[$last]['middleware'] = array_merge($this->routes[$last]['middleware'], $list);

        return $this;
    }

    /**
     * Group routes under a shared path prefix and/or middleware list.
     *
     * @param array{prefix?: string, middleware?: string|array} $attributes
     */
    public function group(array $attributes, callable $routes): void
    {
        $prefix = isset($attributes['prefix']) ? '/' . trim((string)$attributes['prefix'], '/') : '';
        $middleware = (array)($attributes['middleware'] ?? []);

        $this->groupStack[] = ['prefix' => $prefix === '/' ? '' : $prefix, 'middleware' => $middleware];
        try {
            $routes($this);
        } finally {
            array_pop($this->groupStack);
        }
    }

    /**
     * Register a short name for a middleware class, e.g. 'auth' => Authenticate::class.
     * Parameters can be passed after a colon: 'role:reviewer' calls new RequireRole('reviewer').
     */
    public function aliasMiddleware(string $alias, string|callable $middleware): void
    {
        $this->middlewareAliases[$alias] = $middleware;
    }

    /**
     * Custom renderer for 404/405 responses (receives status, request and allowed methods).
     */
    public function setFallback(callable $fallback): void
    {
        $this->fallback = $fallback;
    }

    public function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '' ? '/' : $path;
    }

    private function compileRoute(string $path): array
    {
        $paramNames = [];
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($matches) use (&$paramNames) {
            $paramNames[] = $matches[1];
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $path);

        $regex = '#^' . $pattern . '$#';
        return [$regex, $paramNames];
    }

    /**
     * Dispatches the given request through its route middleware to the handler.
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $this->normalizePath($request->getPath());

        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            $allowedMethods[] = $route['method'];
            if ($route['method'] !== $method) {
                continue;
            }

            $params = [];
            foreach ($route['paramNames'] as $paramName) {
                if (isset($matches[$paramName])) {
                    $params[$paramName] = $matches[$paramName];
                }
            }
            $request->setRouteParams($params);

            $pipeline = new Pipeline(array_map(fn($m) => $this->resolveMiddleware($m), $route['middleware']));
            return $pipeline->process($request, fn(Request $req) => $this->invokeHandler($route['handler'], $req));
        }

        $allowedMethods = array_values(array_unique($allowedMethods));
        $status = $allowedMethods ? 405 : 404;

        if ($this->fallback !== null) {
            $response = ($this->fallback)($status, $request, $allowedMethods);
            if ($status === 405) {
                $response->setHeader('Allow', implode(', ', $allowedMethods));
            }
            return $response;
        }

        return $this->defaultErrorResponse($status, $request, $path, $allowedMethods);
    }

    private function defaultErrorResponse(int $status, Request $request, string $path, array $allowedMethods): Response
    {
        $isApi = $request->expectsJson();

        if ($status === 405) {
            $headers = ['Allow' => implode(', ', $allowedMethods)];
            return $isApi
                ? Response::json(['message' => 'Method Not Allowed', 'allowed_methods' => $allowedMethods], 405, $headers)
                : Response::html('<h1>405 Method Not Allowed</h1><p>Allowed methods: ' . implode(', ', $allowedMethods) . '</p>', 405, $headers);
        }

        return $isApi
            ? Response::json(['message' => 'Not Found', 'path' => $path], 404)
            : Response::html('<h1>404 Not Found</h1><p>The requested URL ' . htmlspecialchars($path) . ' was not found on this server.</p>', 404);
    }

    private function resolveMiddleware(mixed $middleware): MiddlewareInterface|callable
    {
        if ($middleware instanceof MiddlewareInterface || $middleware instanceof \Closure) {
            return $middleware;
        }

        if (!is_string($middleware)) {
            throw new \RuntimeException('Invalid middleware definition.');
        }

        [$name, $paramString] = array_pad(explode(':', $middleware, 2), 2, null);
        $params = $paramString !== null && $paramString !== '' ? explode(',', $paramString) : [];
        $target = $this->middlewareAliases[$name] ?? $name;

        if (is_callable($target) && !is_string($target)) {
            return $target(...$params);
        }

        if (!is_string($target) || !class_exists($target)) {
            throw new \RuntimeException("Unknown middleware: {$name}");
        }

        return new $target(...$params);
    }

    private function invokeHandler(callable|array $handler, Request $request): Response
    {
        if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
            [$class, $method] = $handler;
            $result = (new $class())->$method($request);
        } elseif (is_callable($handler)) {
            $result = $handler($request);
        } else {
            throw new \RuntimeException('Invalid route handler specified.');
        }

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::html((string)$result);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
