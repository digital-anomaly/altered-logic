<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents\Internal;

use DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatch\DocSearchable;
use DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatch\GatedBatchItemWithDocSearchableInterface;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\GatedBatchItemWithPendingEmbeddingInterface;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\PendingEmbedding;
use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Item\GatedBatchItemInterface;
use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Item\GatedBatchItemTrait;

/**
 * Represents a document searchable that we're resolving the details for, ready to save for searching against later.
 *
 * The search details aren't known to begin with (e.g. its embedding vector), but they'll be added as they're resolved.
 */
final class DocSearchableGatedBatchItem implements
    GatedBatchItemInterface,
    GatedBatchItemWithDocSearchableInterface,
    GatedBatchItemWithPendingEmbeddingInterface
{
    /** @use GatedBatchItemTrait<DocSearchableGatedBatchItem> */
    use GatedBatchItemTrait;



    /**
     * Constructor.
     *
     * @param DocSearchable    $docSearchable    The doc-searchable.
     * @param PendingEmbedding $pendingEmbedding The pending embedding.
     */
    public function __construct(
        public DocSearchable $docSearchable,
        public PendingEmbedding $pendingEmbedding,
    ) {}



    /**
     * Get the doc-searchable.
     *
     * @return DocSearchable
     */
    public function getDocSearchable(): DocSearchable
    {
        return $this->docSearchable;
    }

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
