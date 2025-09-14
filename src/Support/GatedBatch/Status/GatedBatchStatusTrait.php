<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\GatedBatch\Status;

use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Batch\GatedBatchInterface;
use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Item\GatedBatchItemInterface;

/**
 * Trait to add to GatedBatchInterface classes, that adds status related methods.
 *
 * @todo - review, is this needed?
 *
 * @template TGatedBatch of GatedBatchInterface
 */
trait GatedBatchStatusTrait
{
    private const string GATED_BATCH_TAG__COMPLETE = 'complete';
    private const string GATED_BATCH_TAG__RESOLVED = 'resolved';



    /**
     * Mark all items as resolved.
     *
     * @param callable|null $callback The callback to use to filter the items.
     * @return void
     */
    public function markItemsAsResolved(?callable $callback = null): void
    {
        $this->addTag(self::GATED_BATCH_TAG__RESOLVED, $callback);
    }

    /**
     * Get the subset of items that have been resolved. Returns a new batch instance.
     *
     * @return TGatedBatch
     */
    public function getResolved(): GatedBatchStatusInterface
    {
        $callback = fn(GatedBatchItemInterface $item) => $item->hasTag(self::GATED_BATCH_TAG__RESOLVED);

        return $this->newFiltered($callback);
    }



    /**
     * Mark all items as complete.
     *
     * @param callable|null $callback The callback to use to filter the items.
     * @return void
     */
    public function markItemsAsCompleted(?callable $callback = null): void
    {
        $this->addTag(self::GATED_BATCH_TAG__COMPLETE, $callback);
    }

    /**
     * Remove all items that have completed the given step.
     *
     * @return void
     */
    public function removeCompletedItems(): void
    {
        $this->removeItemsWithTag(self::GATED_BATCH_TAG__COMPLETE);
    }
}
