<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\Internal;

use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\DTOs\EmbedGatedBatchIdentityDTO;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\EmbedGatedBatchInterface;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\EmbedGatedBatchTrait;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\PendingEmbedding;
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
     * @param EmbedGatedBatchIdentityDTO         $identity The identity of the batch.
     * @param array<integer,EmbedGatedBatchItem> $items    The items to add to the batch.
     */
    public function __construct(EmbedGatedBatchIdentityDTO $identity, array $items = [])
    {
        $this->embedModelProfile = $identity->embedModelProfile;
        $this->embedCacheProfile = $identity->embedCacheProfile;
        $this->embedFaker = $identity->embedFaker;
        $this->debugLevel = $identity->debugLevel;

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
