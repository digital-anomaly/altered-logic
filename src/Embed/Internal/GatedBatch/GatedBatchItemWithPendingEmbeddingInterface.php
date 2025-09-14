<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch;

/**
 * An interface for gated-batch item classes that have a pending embedding.
 */
interface GatedBatchItemWithPendingEmbeddingInterface
{
    /**
     * Get the pending embedding.
     *
     * @return PendingEmbedding
     */
    public function getPendingEmbedding(): PendingEmbedding;
}
