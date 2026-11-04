<?php

declare(strict_types=1);

namespace FlowForge\Core;

/**
 * Custom Method/Path Matcher Router.
 * Supports static routes, parameterized dynamic routes ({id}),
 * and distinguishes 404 Not Found from 405 Method Not Allowed.
 */
class Router
{
    /**
     * @var array<int, array{method: string, path: string, regex: string, paramNames: array<string>, handler: callable|array}>
     */
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function patch(string $path, callable|array $handler): void
    {
        $this->addRoute('PATCH', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    public function addRoute(string $method, string $path, callable|array $handler): void
    {
        $normalizedPath = $this->normalizePath($path);
        [$regex, $paramNames] = $this->compileRoute($normalizedPath);

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $normalizedPath,
            'regex' => $regex,
            'paramNames' => $paramNames,
            'handler' => $handler,
        ];
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
     * Dispatches the given request.
     * Returns Response or throws RouteNotFoundException / MethodNotAllowedException.
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $this->normalizePath($request->getPath());

        $matchedRoutes = [];
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                $allowedMethods[] = $route['method'];
                if ($route['method'] === $method) {
                    $matchedRoutes[] = [
                        'route' => $route,
                        'matches' => $matches,
                    ];
                }
            }
        }

        if (!empty($matchedRoutes)) {
            $match = $matchedRoutes[0];
            $route = $match['route'];
            $params = [];
            foreach ($route['paramNames'] as $paramName) {
                if (isset($match['matches'][$paramName])) {
                    $params[$paramName] = $match['matches'][$paramName];
                }
            }

            $request->setRouteParams($params);
            return $this->invokeHandler($route['handler'], $request);
        }

        // If path matched other methods, return 405 Method Not Allowed
        if (!empty($allowedMethods)) {
            $uniqueAllowed = array_values(array_unique($allowedMethods));
            $isApi = $request->isApiRoute() || $request->isJson();
            if ($isApi) {
                return Response::json([
                    'message' => 'Method Not Allowed',
                    'allowed_methods' => $uniqueAllowed,
                ], 405, ['Allow' => implode(', ', $uniqueAllowed)]);
            }

            return Response::html("<h1>405 Method Not Allowed</h1><p>Allowed methods: " . implode(', ', $uniqueAllowed) . "</p>", 405, [
                'Allow' => implode(', ', $uniqueAllowed)
            ]);
        }

        // 404 Not Found
        $isApi = $request->isApiRoute() || $request->isJson();
        if ($isApi) {
            return Response::json([
                'message' => 'Not Found',
                'path' => $path,
            ], 404);
        }

        // For web routes, we will let Application handle view rendering or standard 404
        return Response::html("<h1>404 Not Found</h1><p>The requested URL {$path} was not found on this server.</p>", 404);
    }

    private function invokeHandler(callable|array $handler, Request $request): Response
    {
        if (is_callable($handler)) {
            $result = $handler($request);
        } elseif (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            if (is_string($class)) {
                $class = new $class();
            }
            $result = $class->$method($request);
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
