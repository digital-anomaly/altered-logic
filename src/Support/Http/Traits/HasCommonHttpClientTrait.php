<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Http\Traits;

use CodeDistortion\Backoff\Backoff;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpPendingRequestInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpStreamInterface;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpTxnDTO;

/**
 * A trait for HTTP clients.
 *
 * @see HttpClientInterface
 */
trait HasCommonHttpClientTrait
{
    /**
     * Add a header to the request.
     *
     * @param string $key   The key of the header.
     * @param string $value The value of the header.
     * @return HttpPendingRequestInterface
     */
    public static function withHeader(string $key, string $value): HttpPendingRequestInterface
    {
        return self::pendingRequest()->withHeader($key, $value);
    }

    /**
     * Add multiple headers to the request.
     *
     * @param array<string,string> $headers The headers to add.
     * @return HttpPendingRequestInterface
     */
    public static function withHeaders(array $headers): HttpPendingRequestInterface
    {
        return self::pendingRequest()->withHeaders($headers);
    }

    /**
     * Add basic authentication to the request.
     *
     * @param string $username The username.
     * @param string $password The password.
     * @return HttpPendingRequestInterface
     */
    public static function withBasicAuth(string $username, string $password): HttpPendingRequestInterface
    {
        return self::pendingRequest()->withBasicAuth($username, $password);
    }

    /**
     * Add a bearer token to the request.
     *
     * @param string $token The token.
     * @return HttpPendingRequestInterface
     */
    public static function withBearerToken(string $token): HttpPendingRequestInterface
    {
        return self::pendingRequest()->withBearerToken($token);
    }





    /**
     * Set the connection timeout.
     *
     * @param integer|float $seconds The timeout in seconds.
     * @return HttpPendingRequestInterface
     */
    public static function withConnectTimeout(int|float $seconds): HttpPendingRequestInterface
    {
        return self::pendingRequest()->withConnectTimeout($seconds);
    }

    /**
     * Set the receive timeout.
     *
     * @param integer|float $seconds The timeout in seconds.
     * @return HttpPendingRequestInterface
     */
    public static function withReceiveTimeout(int|float $seconds): HttpPendingRequestInterface
    {
        return self::pendingRequest()->withReceiveTimeout($seconds);
    }





    /**
     * Specify the backoff strategy to use.
     *
     * @param Backoff|null $backoff The backoff strategy to use.
     * @return HttpPendingRequestInterface
     */
    public static function withBackoff(?Backoff $backoff): HttpPendingRequestInterface
    {
        return self::pendingRequest()->withBackoff($backoff);
    }





    /**
     * Send a GET request.
     *
     * @param string              $url    The url to send the request to.
     * @param array<string,mixed> $params The GET parameters to send with the request.
     * @return HttpTxnDTO
     */
    public static function get(string $url, array $params = []): HttpTxnDTO
    {
        return self::pendingRequest()->get($url, $params);
    }

    /**
     * Send a GET request, and stream the response.
     *
     * @param string              $url    The url to send the request to.
     * @param array<string,mixed> $params The GET parameters to send with the request.
     * @return HttpStreamInterface
     */
    public static function streamGet(string $url, array $params = []): HttpStreamInterface
    {
        // todo - test this
        return self::pendingRequest()->streamGet($url, $params);
    }

    /**
     * Send a POST request.
     *
     * @param string              $url    The url to send the request to.
     * @param array<string,mixed> $params The POST parameters to send with the request.
     * @return HttpTxnDTO
     */
    public static function post(string $url, array $params = []): HttpTxnDTO
    {
        return self::pendingRequest()->post($url, $params);
    }

    /**
     * Send a POST request, and stream the response.
     *
     * @param string              $url    The url to send the request to.
     * @param array<string,mixed> $params The POST parameters to send with the request.
     * @return HttpStreamInterface
     */
    public static function streamPost(string $url, array $params = []): HttpStreamInterface
    {
        // todo - test this
        return self::pendingRequest()->streamPost($url, $params);
    }





    /**
     * Build an HttpTxnDTO representing the current state of the client.
     *
     * This is useful when streaming responses (as the stream is what's returned originally. e.g. ->streamPost()).
     *
     * @return HttpTxnDTO|null
     */
    public static function httpTxn(): ?HttpTxnDTO
    {
        // todo - test this
        return null;
    }
}
