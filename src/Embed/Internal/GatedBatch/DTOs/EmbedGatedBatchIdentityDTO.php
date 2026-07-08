<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\DTOs;

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
     * @param EmbedModelProfile      $embedModelProfile The model profile to use.
     * @param EmbedCacheProfile|null $embedCacheProfile The cache profile to use.
     * @param EmbedFaker|null        $embedFaker        The faker to use when generating embeddings.
     * @param integer                $debugLevel        The debug level to use.
     */
    public function __construct(
        public EmbedModelProfile $embedModelProfile,
        public ?EmbedCacheProfile $embedCacheProfile,
        public ?EmbedFaker $embedFaker,
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

        return "{$embedModelProfileKey}:{$embedCacheProfileKey}:{$fakerKey}:{$this->debugLevel}";
    }
}
