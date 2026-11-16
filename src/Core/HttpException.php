<?php

declare(strict_types=1);

namespace FlowForge\Core;

/**
 * Exception carrying an HTTP status code. Thrown anywhere in the request
 * lifecycle, it is turned into the matching HTML error page or JSON error.
 */
class HttpException extends \RuntimeException
{
    public function __construct(private int $statusCode, string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
