<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch;

use DigitalAnomaly\AlteredLogic\Embed\EmbedFaker;
use DigitalAnomaly\AlteredLogic\Embed\Vector;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedCacheProfile;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedModelProfile;

/**
 * An interface for embed gated batch classes.
 */
interface EmbedGatedBatchInterface
{
    /** @var EmbedModelProfile|null The embed model profile. */
    public ?EmbedModelProfile $embedModelProfile { get; }

    /** @var EmbedCacheProfile|null The embed cache profile. */
    public ?EmbedCacheProfile $embedCacheProfile { get; }

    /** @var EmbedFaker|null The embed faker. */
    public ?EmbedFaker $embedFaker { get; }

    /** @var integer The debug level. */
    public int $debugLevel { get; }



    /**
     * Pluck the embed source strings.
     *
     * @return array<integer,string>
     */
    public function pickSources(): array;

    /**
     * Pluck the unique embed source strings.
     *
     * @return array<integer,string>
     */
    public function pickUniqueSources(): array;

    /**
     * Pick and store the embed vectors.
     *
     * @param array<string,Vector|null> $vectors The embed vectors.
     * @return void
     */
    public function pickAndRecordEmbeddingVectors(array $vectors): void;

    /**
     * Remove the items from this gated batch that have a resolved embedding.
     *
     * @return $this
     */
    public function purgeItemsWithResolvedEmbedding(): self;
}
