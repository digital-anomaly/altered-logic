<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission;

use DigitalAnomaly\AlteredLogic\Support\DTOs\ActiveDurationDTO;

/**
 * Representation of metadata about an embed transmission to an AI provider.
 */
final readonly class EmbedTxnMetaDTO
{
    /**
     * Constructor.
     *
     * @param ActiveDurationDTO  $activeDuration The duration of the request / response.
     * @param EmbedTokenUsageDTO $tokensUsed     The number of AI provider tokens used.
     */
    public function __construct(
        public ActiveDurationDTO $activeDuration,
        public EmbedTokenUsageDTO $tokensUsed,
    ) {}
}
