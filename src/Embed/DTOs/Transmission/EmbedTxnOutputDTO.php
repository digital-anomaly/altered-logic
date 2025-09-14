<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission;

use DigitalAnomaly\AlteredLogic\Embed\Vector;

/**
 * Representation of the response output from an embed transmission to an AI provider.
 */
final readonly class EmbedTxnOutputDTO
{
    /**
     * Constructor.
     *
     * @param integer                    $httpStatusCode   The HTTP response status code.
     * @param string                     $httpStatusReason The HTTP response status code reason.
     * @param string                     $resolvedModel    The actual model that was used.
     * @param boolean                    $maxTokensReached Whether the max-tokens were reached or not.
     * @param string|null                $errorMessage     The error message from the AI provider.
     * @param string|null                $errorDetails     The error details from the AI provider.
     * @param array<integer,Vector|null> $embeddings       The embeddings received in the response.
     *
     */
    public function __construct(
        public int $httpStatusCode,
        public string $httpStatusReason,
        public string $resolvedModel,
        public bool $maxTokensReached,
        public ?string $errorMessage,
        public ?string $errorDetails,
        public array $embeddings,
    ) {}
}
