<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatch;

/**
 * An interface for gated-batch item classes that have a doc-searchable.
 */
interface GatedBatchItemWithDocSearchableInterface
{
    /**
     * Get the document searchable.
     *
     * @return DocSearchable
     */
    public function getDocSearchable(): DocSearchable;
}
