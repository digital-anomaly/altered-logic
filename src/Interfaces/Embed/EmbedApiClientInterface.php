<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Interfaces\Embed;

use DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission\EmbedTxnDTO;
use DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission\EmbedTxnInputDTO;
use DigitalAnomaly\AlteredLogic\Embed\Internal\EmbedConnectionReference;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpPendingRequestInterface;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpTxnDTO;

/**
 * Interface for Embed API clients.
 */
interface EmbedApiClientInterface
{
    /**
     * Build body of the request to send to the AI provider.
     *
     * @param EmbedTxnInputDTO $embedInput The EmbedTxnInputDTO to use.
     * @return string
     */
    public function buildRequestBody(EmbedTxnInputDTO $embedInput): string;

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
     * Build an EmbedTxnDTO based on the response from the AI provider.
     *
     * @param EmbedTxnInputDTO         $embedInput          The EmbedsTxnInputDTO used.
     * @param HttpTxnDTO               $httpTxn             The transmission to analyse.
     * @param EmbedConnectionReference $connectionReference Details about the connection used.
     * @return EmbedTxnDTO
     */
    public function buildResponse(
        EmbedTxnInputDTO $embedInput,
        HttpTxnDTO $httpTxn,
        EmbedConnectionReference $connectionReference,
    ): EmbedTxnDTO;
}
