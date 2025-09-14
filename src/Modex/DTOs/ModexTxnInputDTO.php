<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\DTOs;

use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexDialogue;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexSchemas;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexSettings;

/**
 * Representation of the request input for a multimodal transmission to an AI provider.
 */
final readonly class ModexTxnInputDTO
{
    /**
     * Constructor.
     *
     * @param ModexSettings $settings The ModexSettings to use.
     * @param ModexSchemas  $schemas  The ModexSchemas to use.
     * @param ModexDialogue $dialogue The ModexDialogue to use.
     */
    public function __construct(
        public ModexSettings $settings,
        public ModexSchemas $schemas,
        public ModexDialogue $dialogue,
    ) {}
}
