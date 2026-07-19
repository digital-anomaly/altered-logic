<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\DTOs;

use DigitalAnomaly\AlteredLogic\Credentials\CredentialsOverride;
use DigitalAnomaly\AlteredLogic\Embed\EmbedFaker;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedCacheProfile;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedModelProfile;

/**
 * Identifies which pooled EmbedGatedBatch a piece of embedding work belongs to.
 */
final readonly class EmbedGatedBatchIdentityDTO
{
    /**
     * Constructor.
     *
     * @param EmbedModelProfile        $embedModelProfile   The model profile to use.
     * @param EmbedCacheProfile|null   $embedCacheProfile   The cache profile to use.
     * @param EmbedFaker|null          $embedFaker          The faker to use when generating embeddings.
     * @param CredentialsOverride|null $credentialsOverride The credentials to use instead of each model's own.
     * @param integer                  $debugLevel          The debug level to use.
     */
    public function __construct(
        public EmbedModelProfile $embedModelProfile,
        public ?EmbedCacheProfile $embedCacheProfile,
        public ?EmbedFaker $embedFaker,
        public ?CredentialsOverride $credentialsOverride,
        public int $debugLevel,
    ) {}

    /**
     * Build the key used to pool batches with this identity.
     *
     * @return string
     */
    public function buildKey(): string
    {
        $embedModelProfileKey = \spl_object_id($this->embedModelProfile);

        $embedCacheProfileKey = \is_object($this->embedCacheProfile)
            ? \spl_object_id($this->embedCacheProfile)
            : '';

        $fakerKey = \is_object($this->embedFaker)
            ? \spl_object_id($this->embedFaker)
            : '';

        // value-based (not spl_object_id) so identical overrides pool together and different overrides never merge
        $credentialsOverrideKey = $this->credentialsOverride?->fingerprint() ?? '';

        return "{$embedModelProfileKey}:{$embedCacheProfileKey}:{$fakerKey}:{$credentialsOverrideKey}:{$this->debugLevel}";
    }
}
