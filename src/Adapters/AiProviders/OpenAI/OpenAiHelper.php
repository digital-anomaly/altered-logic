<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI;

use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpPendingRequestInterface;

/**
 * Helper methods for OpenAI API clients.
 */
final class OpenAiHelper
{
    /**
     * Build a new HTTP pending request.
     *
     * @param HttpClientInterface|HttpPendingRequestInterface $httpClient    The HTTP client to use to send the request.
     * @param string                                          $apiKey        The API key to use.
     * @param string|null                                     $organisation  The organisation to use.
     * @param string|null                                     $projectId     The project id to use.
     * @param array<string,string>                            $customHeaders Custom headers to include in the request.
     * @return HttpClientInterface|HttpPendingRequestInterface
     */
    public static function prepareHttpClient(
        HttpClientInterface|HttpPendingRequestInterface $httpClient,
        string $apiKey,
        ?string $organisation,
        ?string $projectId,
        array $customHeaders = [],
    ): HttpClientInterface|HttpPendingRequestInterface {

        // apply standard OpenAI headers
        $httpClient
            ->withHeader('Content-Type', 'application/json')
            ->withBearerToken($apiKey);

        if (($organisation !== null) && ($organisation !== '')) {
            $httpClient->withHeader('OpenAI-Organization', $organisation);
        }

        if (($projectId !== null) && ($projectId !== '')) {
            $httpClient->withHeader('OpenAI-Project', $projectId);
        }

        // apply custom headers
        foreach ($customHeaders as $headerName => $headerValue) {
            if (($headerName !== null) && ($headerName !== '')) {
                if (($headerValue !== null) && ($headerValue !== '')) {
                    $httpClient->withHeader($headerName, $headerValue);
                }
            }
        }

        return $httpClient;
    }
}
