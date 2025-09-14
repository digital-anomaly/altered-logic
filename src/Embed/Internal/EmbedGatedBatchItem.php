<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\Internal;

use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\GatedBatchItemWithPendingEmbeddingInterface;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\PendingEmbedding;
use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Item\GatedBatchItemInterface;
use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Item\GatedBatchItemTrait;

/**
 * Represents a document searchable that we're resolving the details for, ready to save for searching against later.
 *
 * The search details aren't known to begin with (e.g. its embedding vector), but they'll be added as they're resolved.
 */
final class EmbedGatedBatchItem implements
    GatedBatchItemInterface,
    GatedBatchItemWithPendingEmbeddingInterface
{
    /** @use GatedBatchItemTrait<EmbedGatedBatchItem> */
    use GatedBatchItemTrait;



    /**
     * Constructor.
     *
     * @param PendingEmbedding $pendingEmbedding The pending embedding.
     */
    public function __construct(
        private PendingEmbedding $pendingEmbedding,
    ) {}



    /**
     * Get the pending embedding.
     *
     * @return PendingEmbedding
     */
    public function getPendingEmbedding(): PendingEmbedding
    {
        return $this->pendingEmbedding;
    }
}
