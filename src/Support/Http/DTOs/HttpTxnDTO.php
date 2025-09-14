<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Http\DTOs;

use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\DurationDTO;

/**
 * Represents an HTTP transmission (request and response).
 */
final readonly class HttpTxnDTO
{
    /**
     * Constructor.
     *
     * @param HttpRequestDTO       $request  The HTTP request.
     * @param HttpResponseDTO|null $response The HTTP response.
     * @param DurationDTO          $duration The duration of the interaction.
     */
    public function __construct(
        public HttpRequestDTO $request,
        public ?HttpResponseDTO $response,
        public DurationDTO $duration,
    ) {}
}
