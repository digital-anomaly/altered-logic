<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\DTOs;

use DigitalAnomaly\AlteredLogic\Support\DTOs\ActiveDurationDTO;

/**
 * Representation of metadata about a multimodal transmission to an AI provider.
 */
final readonly class ModexTxnMetaDTO
{
    /**
     * Constructor.
     *
     * @param ActiveDurationDTO  $activeDuration The duration of the request / response.
     * @param ModexTokenUsageDTO $tokensUsed     The number of AI provider tokens used.
     */
    public function __construct(
        public ActiveDurationDTO $activeDuration,
        public ModexTokenUsageDTO $tokensUsed,
    ) {}
}
