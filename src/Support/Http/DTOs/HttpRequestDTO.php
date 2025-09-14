<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Http\DTOs;

/**
 * Represents an HTTP request.
 */
final readonly class HttpRequestDTO
{
    /**
     * Constructor.
     *
     * @param string                 $method  The HTTP request method.
     * @param string                 $url     The request URL.
     * @param array<string,mixed>    $params  The GET/POST parameters.
     * @param array<string,string[]> $headers The request headers.
     * @param string                 $body    The request body.
     */
    public function __construct(
        public string $method,
        public string $url,
        public array $params,
        public array $headers,
        public string $body,
    ) {}
}
