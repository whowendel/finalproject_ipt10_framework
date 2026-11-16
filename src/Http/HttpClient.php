<?php

declare(strict_types=1);

namespace FlowForge\Http;

/**
 * Minimal outgoing HTTP client built on cURL (with a stream fallback).
 * Never throws for network problems: failures come back as an HttpResponse
 * with status 0 and an error message, so callers can degrade gracefully.
 */
class HttpClient
{
    public function __construct(
        private int $timeoutSeconds = 5,
        private ?string $caBundle = null,
        private string $userAgent = 'FlowForge-PHP-Framework/1.0'
    ) {
    }

    /**
     * @param array<string, scalar> $query
     * @param array<string, string> $headers
     */
    public function get(string $url, array $query = [], array $headers = []): HttpResponse
    {
        if ($query) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        $headers = array_merge(['Accept' => 'application/json'], $headers);

        return function_exists('curl_init')
            ? $this->sendWithCurl($url, $headers)
            : $this->sendWithStream($url, $headers);
    }

    private function sendWithCurl(string $url, array $headers): HttpResponse
    {
        $responseHeaders = [];
        $ch = curl_init($url);

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_HTTPHEADER => array_map(fn($k, $v) => "{$k}: {$v}", array_keys($headers), $headers),
            CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$responseHeaders): int {
                if (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    $responseHeaders[strtolower(trim($name))] = trim($value);
                }
                return strlen($line);
            },
        ];
        if ($this->caBundle) {
            $options[CURLOPT_CAINFO] = $this->caBundle;
        }
        curl_setopt_array($ch, $options);

        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = $body === false ? (curl_error($ch) ?: 'Unknown cURL error') : null;

        return new HttpResponse($error ? 0 : $status, is_string($body) ? $body : '', $responseHeaders, $error);
    }

    private function sendWithStream(string $url, array $headers): HttpResponse
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", array_map(fn($k, $v) => "{$k}: {$v}", array_keys($headers), $headers)),
                'timeout' => $this->timeoutSeconds,
                'ignore_errors' => true,
                'user_agent' => $this->userAgent,
            ],
            'ssl' => $this->caBundle ? ['cafile' => $this->caBundle] : [],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return new HttpResponse(0, '', [], error_get_last()['message'] ?? 'HTTP request failed');
        }

        $status = 0;
        $responseHeaders = [];
        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                $status = (int)$m[1];
            } elseif (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $responseHeaders[strtolower(trim($name))] = trim($value);
            }
        }

        return new HttpResponse($status, $body, $responseHeaders);
    }
}
