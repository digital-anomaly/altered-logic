<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission;

/**
 * Representation of the request input for an embeddings transmission to an AI provider.
 */
final class EmbedTxnInputDTO
{
    /**
     * Constructor.
     *
     * @param string[] $inputs The inputs to embed.
     */
    public function __construct(
        public array $inputs,
    ) {}
}
