<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Interfaces\Modex;

use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpPendingRequestInterface;
use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTxnDTO;
use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTxnInputDTO;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpTxnDTO;

/**
 * Interface for Modex API clients.
 */
interface ModexApiClientInterface
{
    /**
     * Build body of the request to send to the AI provider.
     *
     * @todo - combine $prevResponseId into ModexTxnInputDTO (or possibly ModexSettings) ?
     *
     * @param ModexTxnInputDTO    $modexInput     The ModexTxnInputDTO to use.
     * @param string|integer|null $prevResponseId The id of the previous response, for conversation continuation.
     * @return string
     */
    public function buildRequestBody(ModexTxnInputDTO $modexInput, string|int|null $prevResponseId): string;

    /**
     * Send the request to the AI provider.
     *
     * @param HttpClientInterface|HttpPendingRequestInterface $httpClient  The HTTP client to use to send the request.
     * @param string                                          $requestBody The request body to send.
     * @return HttpTxnDTO
     */
    public function sendRequest(
        HttpClientInterface|HttpPendingRequestInterface $httpClient,
        string $requestBody,
    ): HttpTxnDTO;

    /**
     * Build an ModexTxnDTO based on the response from the AI provider.
     *
     * @param ModexTxnInputDTO $modexInput The ModexTxnInputDTO used.
     * @param HttpTxnDTO       $httpTxn    The transmission to analyse.
     * @return ModexTxnDTO
     */
    public function buildResponse(ModexTxnInputDTO $modexInput, HttpTxnDTO $httpTxn): ModexTxnDTO;
}
