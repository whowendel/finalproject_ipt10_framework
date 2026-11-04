<?php

declare(strict_types=1);

namespace FlowForge\Core;

/**
 * Normalized HTTP Request representation.
 * Supports query parameters, form data, JSON body parsing with malformed detection,
 * headers, and route parameters.
 */
class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $post;
    private array $headers;
    private ?string $rawBody;
    private ?array $jsonBody = null;
    private bool $hasJsonError = false;
    private ?string $jsonErrorMessage = null;
    private array $routeParams = [];

    public function __construct(
        string $method = 'GET',
        string $uri = '/',
        array $query = [],
        array $post = [],
        array $headers = [],
        ?string $rawBody = null
    ) {
        $this->method = strtoupper($method);
        $this->path = $this->normalizePath($uri);
        $this->query = $query;
        $this->post = $post;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
        $this->rawBody = $rawBody;

        $this->parseJsonBody();
    }

    public static function capture(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Query parameters
        $query = $_GET;

        // Post parameters
        $post = $_POST;

        // Extract headers
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $name = str_replace('_', '-', strtolower($key));
                $headers[$name] = $value;
            }
        }

        $rawBody = file_get_contents('php://input');
        if ($rawBody === false) {
            $rawBody = null;
        }

        return new self($method, $uri, $query, $post, $headers, $rawBody);
    }

    private function normalizePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = '/' . trim($path, '/');
        return $path === '' ? '/' : $path;
    }

    private function parseJsonBody(): void
    {
        $contentType = $this->header('content-type', '');
        $isJsonContent = str_contains($contentType, 'application/json');

        if (($isJsonContent || $this->isApiRoute()) && $this->rawBody !== null && trim($this->rawBody) !== '') {
            $decoded = json_decode($this->rawBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->hasJsonError = true;
                $this->jsonErrorMessage = 'Malformed JSON body: ' . json_last_error_msg();
                $this->jsonBody = null;
            } else {
                $this->jsonBody = is_array($decoded) ? $decoded : [];
            }
        }
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function getPost(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($this->jsonBody === null) {
            return $default;
        }
        if ($key === null) {
            return $this->jsonBody;
        }
        return $this->jsonBody[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if ($this->jsonBody !== null && array_key_exists($key, $this->jsonBody)) {
            return $this->jsonBody[$key];
        }
        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }
        if (array_key_exists($key, $this->query)) {
            return $this->query[$key];
        }
        return $default;
    }

    /**
     * Get all inputs combined (JSON or POST merged with query).
     */
    public function all(): array
    {
        $body = $this->jsonBody ?? $this->post;
        return array_merge($this->query, $body);
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getRawBody(): ?string
    {
        return $this->rawBody;
    }

    public function hasJsonError(): bool
    {
        return $this->hasJsonError;
    }

    public function getJsonErrorMessage(): ?string
    {
        return $this->jsonErrorMessage;
    }

    public function isJson(): bool
    {
        $contentType = $this->header('content-type', '');
        return str_contains($contentType, 'application/json');
    }

    public function isApiRoute(): bool
    {
        return str_starts_with($this->path, '/api/') || $this->path === '/api';
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }
}
