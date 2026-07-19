<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch;

use DigitalAnomaly\AlteredLogic\Credentials\CredentialsOverride;
use DigitalAnomaly\AlteredLogic\Embed\EmbedFaker;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\EmbedGatedBatchInterface;
use DigitalAnomaly\AlteredLogic\Embed\Vector;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedCacheProfile;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedModelProfile;

/**
 * Trait to add to EmbedGatedBatchInterface classes.
 *
 * @template TEmbedGatedBatchItem of GatedBatchItemWithPendingEmbeddingInterface
 */
trait EmbedGatedBatchTrait
{
    /** @var EmbedModelProfile|null The embed model profile. */
    public private(set) ?EmbedModelProfile $embedModelProfile;

    /** @var EmbedCacheProfile|null The embed cache profile. */
    public private(set) ?EmbedCacheProfile $embedCacheProfile;

    /** @var EmbedFaker|null The embed faker. */
    public private(set) ?EmbedFaker $embedFaker;

    /** @var CredentialsOverride|null The credentials override to use (instead of each model's own credentials). */
    public private(set) ?CredentialsOverride $credentialsOverride;

    /** @var integer The debug level. */
    public private(set) int $debugLevel;



    /**
     * Pluck the embedding source strings.
     *
     * @return array<integer,string>
     */
    public function pickSources(): array
    {
        return \array_map(
            fn(GatedBatchItemWithPendingEmbeddingInterface $item) => $item->getPendingEmbedding()->source,
            $this->items,
        );
    }

    /**
     * Pluck the unique embedding source strings.
     *
     * @return array<integer,string>
     */
    public function pickUniqueSources(): array
    {
        return \array_unique($this->pickSources());
    }

    /**
     * Pick and record the embedding vectors.
     *
     * @param array<string,Vector|null> $vectors The embedding vectors.
     * @return void
     */
    public function pickAndRecordEmbeddingVectors(array $vectors): void
    {
        /** @var TEmbedGatedBatchItem $item */
        foreach ($this as $item) {

            $vector = $vectors[$item->getPendingEmbedding()->source] ?? null;

            if ($vector === null) {
                continue;
            }

            $item->getPendingEmbedding()->setVector($vector);
        }
    }

    /**
     * Remove the items from this gated batch that have a resolved embedding.
     *
     * @return $this
     */
    public function purgeItemsWithResolvedEmbedding(): EmbedGatedBatchInterface
    {
        $callback = fn(GatedBatchItemWithPendingEmbeddingInterface $item): bool => $item->getPendingEmbedding()->vector !== null;

        return $this->removeItems($callback);
    }
}
