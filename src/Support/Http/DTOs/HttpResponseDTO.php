<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Http\DTOs;

/**
 * Represents an HTTP response.
 */
final readonly class HttpResponseDTO
{
    /**
     * Constructor.
     *
     * @param integer                $statusCode   The response status code.
     * @param string                 $statusReason The reason for the status code.
     * @param array<string,string[]> $headers      The response headers.
     * @param string                 $body         The response body.
     */
    public function __construct(
        public int $statusCode,
        public string $statusReason,
        public array $headers,
        public string $body,
    ) {}



    /**
     * Check if the response was successful.
     *
     * @return boolean
     */
    public function wasSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if the response should be retried (because it failed, but might succeed on a retry).
     *
     * @return boolean
     */
    public function shouldRetry(): bool
    {
        if ($this->statusCode >= 500 && $this->statusCode < 600) {
            return true;
        }

        if ($this->statusCode >= 400 && $this->statusCode < 500) {

            return \in_array(
                $this->statusCode,
                [
                    // 400, // Bad Request
                    401, // Unauthorized
                    402, // Payment Required
                    403, // Forbidden
                    // 404, // Not Found
                    // 405, // Method Not Allowed
                    // 406, // Not Acceptable
                    // 407, // Proxy Authentication Required
                    408, // Request Timeout
                    409, // Conflict
                    // 410, // Gone
                    // 411, // Length Required
                    // 412, // Precondition Failed
                    // 413, // Payload Too Large
                    // 414, // URI Too Long
                    // 415, // Unsupported Media Type
                    // 416, // Range Not Satisfiable
                    // 417, // Expectation Failed
                    // 418, // I'm a teapot
                    421, // Misdirected Request
                    // 422, // Unprocessable Entity
                    423, // Locked
                    424, // Failed Dependency
                    425, // Too Early
                    // 426, // Upgrade Required
                    428, // Precondition Required
                    429, // Too Many Requests
                    // 431, // Request Header Fields Too Large
                    // 451, // Unavailable For Legal Reasons
                ],
                true,
            );
        }

        return false;
    }


    /**
     * Convert the response body to a JSON array.
     *
     * @return array<mixed>
     */
    public function toJson(): array
    {
        $return = \json_decode($this->body, true);

        return \is_array($return)
            ? $return
            : [];
    }
}
