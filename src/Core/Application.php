<?php

declare(strict_types=1);

namespace FlowForge\Core;

use FlowForge\Support\Csrf;

/**
 * FlowForge Application Kernel.
 * Coordinates configuration, database bootstrapping, routing, request lifecycle,
 * and content-aware error handling.
 */
class Application
{
    private string $basePath;
    private array $config;
    private Router $router;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->loadConfiguration();
        $this->router = new Router();
        $this->bootstrap();
    }

    private function loadConfiguration(): void
    {
        $configFile = $this->basePath . '/config/app.php';
        if (file_exists($configFile)) {
            $this->config = require $configFile;
        } else {
            $this->config = [
                'app_name' => 'FlowForge',
                'env' => 'development',
                'debug' => true,
            ];
        }
    }

    private function bootstrap(): void
    {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            $sessionLifetime = $this->config['session']['lifetime'] ?? 7200;
            ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
            session_start();
        }

        // Configure View path
        View::setViewsPath($this->basePath . '/views');
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Load route definitions from routes/web.php and routes/api.php.
     */
    public function loadRoutes(): void
    {
        $router = $this->router;

        $webRoutes = $this->basePath . '/routes/web.php';
        if (file_exists($webRoutes)) {
            require $webRoutes;
        }

        $apiRoutes = $this->basePath . '/routes/api.php';
        if (file_exists($apiRoutes)) {
            require $apiRoutes;
        }
    }

    /**
     * Handle incoming request and return HTTP Response.
     */
    public function handle(Request $request): Response
    {
        try {
            // Check for malformed JSON if API route or JSON content
            if ($request->hasJsonError()) {
                return Response::json([
                    'message' => 'Malformed JSON payload',
                    'error' => $request->getJsonErrorMessage(),
                ], 400);
            }

            // CSRF validation for state-changing browser requests (POST/PUT/PATCH/DELETE)
            if (!$request->isApiRoute() && in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                $token = $request->input('_csrf');
                if (!Csrf::verify($token)) {
                    return Response::html(
                        View::render('errors/419', [
                            'message' => 'Page expired or invalid CSRF token. Please refresh and try again.'
                        ], 'layouts/main'),
                        419
                    );
                }
            }

            return $this->router->dispatch($request);
        } catch (\Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    /**
     * Centralized content-aware exception handler.
     */
    private function handleException(\Throwable $e, Request $request): Response
    {
        $isDebug = $this->config['debug'] ?? false;
        $isApi = $request->isApiRoute() || $request->isJson();

        $statusCode = 500;
        if ($e->getCode() >= 400 && $e->getCode() < 600) {
            $statusCode = (int)$e->getCode();
        }

        if ($isApi) {
            $payload = [
                'message' => $e->getMessage() ?: 'Internal Server Error',
            ];
            if ($isDebug) {
                $payload['exception'] = get_class($e);
                $payload['file'] = $e->getFile();
                $payload['line'] = $e->getLine();
            }
            return Response::json($payload, $statusCode);
        }

        // HTML Browser error
        $viewData = [
            'statusCode' => $statusCode,
            'message' => $e->getMessage() ?: 'An unexpected error occurred.',
            'debug' => $isDebug,
            'exception' => $e,
        ];

        try {
            $viewName = $statusCode === 404 ? 'errors/404' : 'errors/500';
            return Response::html(View::render($viewName, $viewData, 'layouts/main'), $statusCode);
        } catch (\Throwable) {
            // Fallback plain HTML if error view fails
            $html = "<h1>Error {$statusCode}</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
            return Response::html($html, $statusCode);
        }
    }

    /**
     * Run application and send response.
     */
    public function run(): void
    {
        $this->loadRoutes();
        $request = Request::capture();
        $response = $this->handle($request);
        $response->send();
    }
}
