<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Interfaces\Http;

use CodeDistortion\Backoff\Backoff;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpTxnDTO;

/**
 * Interface for a simple HTTP client.
 */
interface HttpPendingRequestInterface
{











    /**
     * Add a header to the request.
     *
     * @param string $key   The key of the header.
     * @param string $value The value of the header.
     * @return HttpPendingRequestInterface
     */
    public function withHeader(string $key, string $value): HttpPendingRequestInterface;

    /**
     * Add multiple headers to the request.
     *
     * @param array<string,string> $headers The headers to add.
     * @return HttpPendingRequestInterface
     */
    public function withHeaders(array $headers): HttpPendingRequestInterface;

    /**
     * Add basic authentication to the request.
     *
     * @param string $username The username.
     * @param string $password The password.
     * @return HttpPendingRequestInterface
     */
    public function withBasicAuth(string $username, string $password): HttpPendingRequestInterface;

    /**
     * Add a bearer token to the request.
     *
     * @param string $token The token.
     * @return HttpPendingRequestInterface
     */
    public function withBearerToken(string $token): HttpPendingRequestInterface;





    /**
     * Set the connection timeout.
     *
     * @param integer|float $seconds The timeout in seconds.
     * @return HttpPendingRequestInterface
     */
    public function withConnectTimeout(int|float $seconds): HttpPendingRequestInterface;

    /**
     * Set the receive timeout.
     *
     * @param integer|float $seconds The timeout in seconds.
     * @return HttpPendingRequestInterface
     */
    public function withReceiveTimeout(int|float $seconds): HttpPendingRequestInterface;





    /**
     * Specify the backoff strategy to use.
     *
     * @param Backoff|null $backoff The backoff strategy to use.
     * @return HttpPendingRequestInterface
     */
    public function withBackoff(?Backoff $backoff): HttpPendingRequestInterface;





    /**
     * Send a GET request.
     *
     * @param string              $url    The url to send the request to.
     * @param array<string,mixed> $params The GET parameters to send with the request.
     * @return HttpTxnDTO
     */
    public function get(string $url, array $params = []): HttpTxnDTO;

    /**
     * Send a GET request, and stream the response.
     *
     * @param string              $url    The url to send the request to.
     * @param array<string,mixed> $params The GET parameters to send with the request.
     * @return HttpStreamInterface
     */
    public function streamGet(string $url, array $params = []): HttpStreamInterface;

    /**
     * Send a POST request.
     *
     * @param string              $url    The url to send the request to.
     * @param array<string,mixed> $params The POST parameters to send with the request.
     * @return HttpTxnDTO
     */
    public function post(string $url, array $params = []): HttpTxnDTO;

    /**
     * Send a POST request, and stream the response.
     *
     * @param string              $url    The url to send the request to.
     * @param array<string,mixed> $params The POST parameters to send with the request.
     * @return HttpStreamInterface
     */
    public function streamPost(string $url, array $params = []): HttpStreamInterface;





    /**
     * Build an HttpTxnDTO representing the current state of the client.
     *
     * This is useful when streaming responses (as the stream is what's returned originally. e.g. ->streamPost()).
     *
     * @return HttpTxnDTO|null
     */
    public function httpTxn(): ?HttpTxnDTO;
}
