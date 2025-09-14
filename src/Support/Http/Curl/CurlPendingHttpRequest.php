<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Http\Curl;

use CurlHandle;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpPendingRequestInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpStreamInterface;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\DurationDTO;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpRequestDTO;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpResponseDTO;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpTxnDTO;
use DigitalAnomaly\AlteredLogic\Support\Http\HttpHelper;
use DigitalAnomaly\AlteredLogic\Support\Http\Traits\HasCommonHttpTrait;

/**
 * A pending cURL HTTP request.
 *
 * AI instructions (static-entry-pattern): This is the "enterable" class, "entered" by the CurlHttpClient class.
 */
final class CurlPendingHttpRequest implements HttpPendingRequestInterface
{
    use HasCommonHttpTrait;



    /**
     * Send a request.
     *
     * @param string              $method The HTTP method to send the request with.
     * @param string              $url    The url to send the request to.
     * @param array<string,mixed> $params The parameters to send with the request.
     * @return HttpTxnDTO
     */
    protected function send(string $method, string $url, array $params = []): HttpTxnDTO
    {
        $requestedUrl = $url;
        $requestHeaders = $responseHeaders = [];
        $requestBody = $responseBody = '';
        $responseReason = '';

        $curlHandle = null;

        $start = \microtime(true);

        try {

            $curlHandle = $this->buildCurlRequest($method, $url, $params);

            $this->captureRequestHeaderAndBody($curlHandle, $requestHeaders, $requestBody);
            $this->captureResponseHeaders($curlHandle, $responseHeaders, $responseReason);

            $responseBody = $this->sendRequest($curlHandle);

            $requestedUrl = \curl_getinfo($curlHandle, \CURLINFO_EFFECTIVE_URL);
            $responseCode = \curl_getinfo($curlHandle, \CURLINFO_HTTP_CODE);
            $responseReason = HttpHelper::resolveHttpReasonPhrase($responseCode, $responseReason);
            $curlError = \curl_error($curlHandle);

        } finally {

            if ($curlHandle !== null) {
                \curl_close($curlHandle);
            }

            $stop = \microtime(true);
        }

        $httpTxn = $this->buildNewHttpTxn(
            $method,
            $requestedUrl,
            $params,
            $start,
            $stop,
            $requestHeaders,
            $requestBody,
            $responseCode,
            $responseHeaders,
            $responseReason,
            $responseBody,
            $curlError,
        );

        $this->setHttpTxn($httpTxn);

        return $httpTxn;
    }





    /**
     * Build a cURL request handle.
     *
     * @param string              $method The HTTP method to send the request with.
     * @param string              $url    The url to send the request to.
     * @param array<string,mixed> $params The parameters to send with the request.
     * @return CurlHandle
     */
    private function buildCurlRequest(string $method, string $url, array $params = []): CurlHandle
    {
        $curlHandle = \curl_init();

        // add authentication headers
        $basicAuth = $this->getBasicAuth();
        if (\is_array($basicAuth)) {
            \curl_setopt($curlHandle, \CURLOPT_USERPWD, "{$basicAuth[0]}:{$basicAuth[1]}");
        }

        // configure the request based on method
        $finalUrl = $url;
        if ($method === 'POST') {

            \curl_setopt($curlHandle, \CURLOPT_POST, true);
            if ($params !== []) {

                $body = \json_encode($params);
                $body = \is_string($body)
                    ? $body
                    : '';

                // \curl_setopt($curlHandle, \CURLOPT_POSTFIELDS, \http_build_query($params));
                \curl_setopt($curlHandle, \CURLOPT_POSTFIELDS, $body);
            }

        } elseif ($method === 'GET') {

            if ($params !== []) {
                $finalUrl .= \str_contains($url, '?')
                    ? '&' . \http_build_query($params)
                    : '?' . \http_build_query($params);
            }
        }

        // set common cURL options
        \curl_setopt_array($curlHandle, [
            \CURLOPT_URL => $finalUrl,
            \CURLOPT_HTTPHEADER => $this->prepareHeaders(),
            \CURLOPT_USERAGENT => $this->buildUserAgent('cURL', $this->getCurlVersion()),
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_FOLLOWLOCATION => true,
            \CURLOPT_MAXREDIRS => 5,
            \CURLOPT_CONNECTTIMEOUT => (int) $this->getConnectionTimeout(),
            \CURLOPT_TIMEOUT => (int) $this->getReceiveTimeout(),
            \CURLOPT_SSL_VERIFYPEER => true,
            \CURLOPT_SSL_VERIFYHOST => 2,
            \CURLOPT_ENCODING => '', // empty string means "all available encodings"
        ]);

        return $curlHandle;
    }

    /**
     * Prepare the headers for the request.
     *
     * @return list<string>
     */
    private function prepareHeaders(): array
    {
        $headers = [];
        foreach ($this->getHeaders() as $key => $value) {
            $headers[] = "{$key}: {$value}";
        }

        $bearerToken = $this->getBearerToken();
        if (\is_string($bearerToken)) {
            $headers[] = "Authorization: Bearer {$bearerToken}";
        }

        return $headers;
    }

    /**
     * Get the cURL version.
     *
     * @return string
     */
    private function getCurlVersion(): string
    {
        $curlVersion = \curl_version();
        if (!\is_array($curlVersion)) {
            return 'unknown';
        }

        if (!\array_key_exists('version', $curlVersion)) {
            return 'unknown';
        }

        if (!\is_string($curlVersion['version'])) {
            return 'unknown';
        }

        return $curlVersion['version'];
    }





    /**
     * Capture the request headers.
     *
     * @param CurlHandle             $curlHandle The cURL handle.
     * @param array<string,string[]> $headers    The request headers.
     * @param string                 $body       The request body.
     * @return void
     */
    private function captureRequestHeaderAndBody(CurlHandle $curlHandle, array &$headers, string &$body): void
    {
        $debugCallback = function ($curl, int $type, string $data) use (&$headers, &$body): int {

            if ($type === \CURLINFO_HEADER_OUT) {
                $headers = $this->parseHeadersString($data, $headers);
            }

            if ($type === \CURLINFO_DATA_OUT) {
                $body .= $data;
            }

            return \mb_strlen($data);
        };

        \curl_setopt($curlHandle, \CURLOPT_DEBUGFUNCTION, $debugCallback);
        \curl_setopt($curlHandle, \CURLOPT_VERBOSE, true);
    }

    /**
     * Capture the response headers.
     *
     * @param CurlHandle             $curlHandle     The cURL handle.
     * @param array<string,string[]> $headers        The response headers.
     * @param string                 $responseReason The HTTP response reason (e.g. "OK", "Not Found", etc).
     * @return void
     */
    private function captureResponseHeaders(CurlHandle $curlHandle, array &$headers, string &$responseReason): void
    {
        $callback = function ($curl, string $header) use (&$headers, &$responseReason): int {

            // add the headers to the array
            $headers = $this->parseHeadersString($header, $headers);

            // extract the response reason
            if (\preg_match('#^HTTP/\d+\.\d+\s+\d+\s+(.+)$#', $header, $matches) === 1) {
                $responseReason = \mb_trim($matches[1]);
            }

            return \mb_strlen($header);
        };

        \curl_setopt($curlHandle, \CURLOPT_HEADERFUNCTION, $callback);
    }

    /**
     * Parse raw headers string and append to an associative header array.
     *
     * @param string                 $rawHeaderString The raw headers string from cURL.
     * @param array<string,string[]> $appendTo        The array to append to.
     * @return array<string,string[]>
     */
    private function parseHeadersString(string $rawHeaderString, array $appendTo = []): array
    {
        $lines = \explode("\n", $rawHeaderString);

        foreach ($lines as $line) {

            $line = \mb_trim($line);

            if (!\str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = \explode(':', $line, 2);
            $key = \mb_trim($key);
            $value = \mb_trim($value);

            $appendTo[$key] ??= [];
            $appendTo[$key][] = $value;
        }

        return $appendTo;
    }





    /**
     * Send a request.
     *
     * @param CurlHandle $curlHandle The cURL handle.
     * @return string
     */
    private function sendRequest(CurlHandle $curlHandle): string
    {
        $responseBody = \curl_exec($curlHandle);

        return \is_string($responseBody)
            ? $responseBody
            : '';
    }





    /**
     * Build a transmission summary.
     *
     * @param string                 $method          The HTTP method to send the request with.
     * @param string                 $url             The url to send the request to.
     * @param array<string,mixed>    $params          The parameters to send with the request.
     * @param float                  $start           The start time.
     * @param float                  $stop            The stop time.
     * @param array<string,string[]> $requestHeaders  The request headers.
     * @param string                 $requestBody     The request body.
     * @param integer                $responseCode    The HTTP response code.
     * @param array<string,string[]> $responseHeaders The response headers.
     * @param string                 $responseReason  The HTTP response reason (e.g. "OK", "Not Found", etc).
     * @param string                 $responseBody    The response body.
     * @param string                 $curlError       The cURL error if any.
     * @return HttpTxnDTO
     */
    private function buildNewHttpTxn(
        string $method,
        string $url,
        array $params,
        float $start,
        float $stop,
        array $requestHeaders,
        string $requestBody,
        int $responseCode,
        array $responseHeaders,
        string $responseReason,
        string $responseBody,
        string $curlError,
    ): HttpTxnDTO {

        $requestDTO = new HttpRequestDTO(
            $method,
            $url,
            $params,
            $this->maskAuthHeaders($requestHeaders),
            $requestBody,
        );

        $responseDTO = $responseCode > 0
            ? new HttpResponseDTO(
                $responseCode,
                $responseReason,
                $responseHeaders,
                $responseBody,
            )
            : null;

        return new HttpTxnDTO(
            $requestDTO,
            $responseDTO,
            DurationDTO::new($start, $stop)
        );
    }





    /**
     * Send a request, and stream the response.
     *
     * @param string              $method The HTTP method to send the request with.
     * @param string              $url    The url to send the request to.
     * @param array<string,mixed> $params The GET parameters to send with the request.
     * @return HttpStreamInterface
     */
    private function streamSend(string $method, string $url, array $params = []): HttpStreamInterface
    {
        // todo
    }





    // /**
    //  * Build an HttpTxnDTO representing the current state of the client.
    //  *
    //  * This is useful when streaming responses (as the stream itself is what's returned originally. e.g.
    //  * ->streamPost()).
    //  *
    //  * @return HttpTxnDTO|null
    //  */
    // public function httpTxn(): ?HttpTxnDTO
    // {
    //     return $this->httpTxn;
    // }
}
