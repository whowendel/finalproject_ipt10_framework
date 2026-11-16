<?php

declare(strict_types=1);

namespace FlowForge\Http;

/**
 * Result of an outgoing HTTP call made with HttpClient.
 */
final class HttpResponse
{
    /**
     * @param array<string, string> $headers lower-cased header names
     */
    public function __construct(
        public readonly int $status,
        public readonly string $body = '',
        public readonly array $headers = [],
        public readonly ?string $error = null
    ) {
    }

    /** True when the call reached the server and got a 2xx answer. */
    public function ok(): bool
    {
        return $this->error === null && $this->status >= 200 && $this->status < 300;
    }

    public function json(): mixed
    {
        $data = json_decode($this->body, true);
        return json_last_error() === JSON_ERROR_NONE ? $data : null;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
