<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\DTOs;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessageInterface;

/**
 * Representation of the response output from a multimodal transmission to an AI provider.
 */
final readonly class ModexTxnOutputDTO
{
    /**
     * Constructor.
     *
     * @param integer                 $httpStatusCode   The HTTP response status code.
     * @param string                  $httpStatusReason The HTTP response status code reason.
     * @param string                  $resolvedModel    The actual model that was used.
     * @param string|integer          $providerId       The id given to this request by the AI provider.
     * @param boolean                 $maxTokensReached Whether the max-tokens were reached or not.
     * @param string|null             $errorMessage     The error message from the AI provider.
     * @param string|null             $errorDetails     The error details from the AI provider.
     * @param array<MessageInterface> $messages         The messages received in the response.
     *
     */
    public function __construct(
        public int $httpStatusCode,
        public string $httpStatusReason,
        public string $resolvedModel,
        public string|int $providerId,
        public bool $maxTokensReached,
        public ?string $errorMessage,
        public ?string $errorDetails,
        public array $messages,
    ) {}
}
