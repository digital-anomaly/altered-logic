<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\Internal;

use DigitalAnomaly\AlteredLogic\Embed\EmbedFaker;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\EmbedGatedBatchInterface;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\EmbedGatedBatchTrait;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\PendingEmbedding;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedCacheProfile;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedModelProfile;
use DigitalAnomaly\AlteredLogic\Support\EmbedHelper;
use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Batch\AbstractGatedBatch;
use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Status\GatedBatchStatusInterface;
use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Status\GatedBatchStatusTrait;

/**
 * Represents a number of sources that we'll resolve the embedding vectors for.
 *
 * The embedding vectors aren't known to begin with, but they'll be added as they're resolved.
 *
 * @extends AbstractGatedBatch<EmbedGatedBatch,EmbedGatedBatchItem>
 */
final class EmbedGatedBatch extends AbstractGatedBatch implements
    EmbedGatedBatchInterface,
    GatedBatchStatusInterface // todo - remove this?
{
    /** @use EmbedGatedBatchTrait<EmbedGatedBatchItem> */
    use EmbedGatedBatchTrait;
    /** @use GatedBatchStatusTrait<EmbedGatedBatch> */
    use GatedBatchStatusTrait;



    /**
     * Create a new batch.
     *
     * @param EmbedModelProfile                  $embedModelProfile The model profile to use.
     * @param EmbedCacheProfile|null             $embedCacheProfile The cache profile to use.
     * @param EmbedFaker|null                    $embedFaker        The faker to use when generating embeddings.
     * @param integer                            $debugLevel        The debug level to use.
     * @param array<integer,EmbedGatedBatchItem> $items             The items to add to the batch.
     */
    public function __construct(
        EmbedModelProfile $embedModelProfile,
        ?EmbedCacheProfile $embedCacheProfile,
        ?EmbedFaker $embedFaker,
        int $debugLevel,
        array $items = [],
    ) {
        $this->embedModelProfile = $embedModelProfile;
        $this->embedCacheProfile = $embedCacheProfile;
        $this->embedFaker = $embedFaker;
        $this->debugLevel = $debugLevel;

        $this->replaceItems($items);
    }



    /**
     * Add items to the batch, ready to be resolved.
     *
     * @param array<string|integer,mixed> $sources The items to embed - non-string items will be encoded as JSON.
     * @return $this
     */
    public function addItemsToResolve(array $sources): self
    {
        $sources = EmbedHelper::normaliseSources($sources);

        foreach ($sources as $source) {

            $pendingEmbedding = new PendingEmbedding($source);

            $batchItem = new EmbedGatedBatchItem($pendingEmbedding);

            $this->addItem($batchItem);
        }

        return $this;
    }
}
