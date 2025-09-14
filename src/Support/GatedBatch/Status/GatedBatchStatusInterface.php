<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\GatedBatch\Status;

/**
 * Represents a gated batch whose items can have statuses.
 *
 * @todo - remove this, if it's not needed
 */
interface GatedBatchStatusInterface
{
    /**
     * Mark all items as resolved.
     *
     * @param callable|null $callback The callback to use to filter the items.
     * @return void
     */
    public function markItemsAsResolved(?callable $callback = null): void;

    /**
     * Get the subset of items that have been resolved. Returns a new batch instance.
     *
     * @return self
     */
    public function getResolved(): self;



    /**
     * Mark all items as complete.
     *
     * @param callable|null $callback The callback to use to filter the items.
     * @return void
     */
    public function markItemsAsCompleted(?callable $callback = null): void;

    /**
     * Remove all items that have completed the given step.
     *
     * @return void
     */
    public function removeCompletedItems(): void;
}
