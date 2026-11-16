<?php

declare(strict_types=1);

namespace FlowForge\Core;

use FlowForge\Middleware\MiddlewareInterface;
use FlowForge\Middleware\RejectMalformedJson;
use FlowForge\Middleware\RequestLogger;
use FlowForge\Middleware\VerifyCsrfToken;
use FlowForge\Support\Logger;

/**
 * FlowForge Application Kernel.
 * Coordinates configuration, sessions, middleware, routing, the request
 * lifecycle, and content-aware error handling.
 *
 *   index.php -> Application::run()
 *     -> Request::capture()
 *     -> global middleware (log -> JSON guard -> CSRF)
 *       -> Router::dispatch() -> route middleware (auth, role) -> controller
 *     -> Response::send()
 */
class Application
{
    private string $basePath;
    private Router $router;

    /** @var array<int, MiddlewareInterface|callable> runs on every request */
    private array $middleware = [];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        Config::load($this->basePath . '/config/app.php');
        $this->router = new Router();
        $this->bootstrap();
    }

    private function bootstrap(): void
    {
        date_default_timezone_set((string)Config::get('timezone', 'UTC'));

        ini_set('display_errors', Config::get('debug') ? '1' : '0');
        error_reporting(E_ALL);

        View::setViewsPath($this->basePath . '/views');

        // Global middleware, outermost first.
        $this->middleware = [
            new RequestLogger(),
            new RejectMalformedJson(),
            new VerifyCsrfToken(),
        ];

        // Render 404/405 with the framework's own error pages.
        $this->router->setFallback(function (int $status, Request $request, array $allowed): Response {
            $message = $status === 405
                ? 'This page does not accept ' . $request->getMethod() . ' requests. Allowed: ' . implode(', ', $allowed) . '.'
                : 'The requested page or record could not be found.';
            return $this->errorResponse($status, $message, $request);
        });
    }

    private function startSession(): void
    {
        if (session_status() !== PHP_SESSION_NONE || headers_sent()) {
            return;
        }

        $lifetime = (int)Config::get('session.lifetime', 7200);
        ini_set('session.gc_maxlifetime', (string)$lifetime);
        ini_set('session.use_strict_mode', '1');
        session_name((string)Config::get('session.name', 'flowforge_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => Url::basePath() === '' ? '/' : Url::basePath() . '/',
            'httponly' => true,
            // Lax: the cookie is not sent on cross-site POSTs, which protects the JSON API from CSRF.
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getConfig(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }

    /**
     * Add a global middleware that runs on every request.
     */
    public function addMiddleware(MiddlewareInterface|callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Load route definitions from routes/web.php and routes/api.php.
     */
    public function loadRoutes(): void
    {
        $router = $this->router;

        // Route middleware aliases used in the route files.
        $router->aliasMiddleware('auth', \App\Middleware\Authenticate::class);
        $router->aliasMiddleware('guest', \App\Middleware\RedirectIfAuthenticated::class);
        $router->aliasMiddleware('role', \App\Middleware\RequireRole::class);

        foreach (['web.php', 'api.php'] as $file) {
            $path = $this->basePath . '/routes/' . $file;
            if (file_exists($path)) {
                require $path;
            }
        }
    }

    /**
     * Handle an incoming request and return the HTTP Response.
     */
    public function handle(Request $request): Response
    {
        try {
            return (new Pipeline($this->middleware))->process($request, function (Request $request): Response {
                try {
                    return $this->router->dispatch($request);
                } catch (\Throwable $e) {
                    return $this->handleException($e, $request);
                }
            });
        } catch (\Throwable $e) {
            // Thrown by a global middleware (e.g. CSRF failure).
            return $this->handleException($e, $request);
        }
    }

    /**
     * Centralized content-aware exception handler.
     */
    private function handleException(\Throwable $e, Request $request): Response
    {
        $statusCode = $e instanceof HttpException ? $e->getStatusCode() : 500;

        if ($statusCode >= 500) {
            Logger::instance()->error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        // Do not leak internal error details unless debugging is on.
        $message = $e instanceof HttpException || Config::get('debug')
            ? ($e->getMessage() ?: 'An unexpected error occurred.')
            : 'An unexpected error occurred while processing your request.';

        return $this->errorResponse($statusCode, $message, $request, $e);
    }

    private function errorResponse(int $status, string $message, Request $request, ?\Throwable $e = null): Response
    {
        $debug = (bool)Config::get('debug', false);

        if ($request->expectsJson()) {
            $payload = ['message' => $message];
            if ($debug && $e !== null && !$e instanceof HttpException) {
                $payload['exception'] = get_class($e);
                $payload['file'] = $e->getFile();
                $payload['line'] = $e->getLine();
            }
            return Response::json($payload, $status);
        }

        $view = in_array($status, [403, 404, 405, 419], true) ? 'errors/' . $status : 'errors/500';

        try {
            return Response::html(View::render($view, [
                'title' => "Error {$status} - FlowForge",
                'statusCode' => $status,
                'message' => $message,
                'debug' => $debug,
                'exception' => $e,
            ]), $status);
        } catch (\Throwable) {
            // Fallback plain HTML if the error view itself fails
            return Response::html("<h1>Error {$status}</h1><p>" . htmlspecialchars($message) . '</p>', $status);
        }
    }

    /**
     * Run the application and send the response.
     */
    public function run(): void
    {
        $request = Request::capture();   // also detects the base URL
        $this->startSession();
        $this->loadRoutes();

        $response = $this->handle($request);
        $response->send();
    }
}
