<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\GatedBatch\Batch;

use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Item\GatedBatchItemInterface;
use Traversable;

/**
 * Represents a collection of items to be processed. The steps that each one has gone through are tracked, so they can
 * proceed, one step at a time.
 *
 * @template TGatedBatch of GatedBatchInterface
 * @template TGatedBatchItem of GatedBatchItemInterface
 */
interface GatedBatchInterface
{
    /**
     * Get an iterator for the items.
     *
     * @return Traversable<integer,TGatedBatchItem>
     */
    public function getIterator(): Traversable;





    /**
     * Add an item to the batch.
     *
     * @param TGatedBatchItem $item The item to add.
     * @return $this
     */
    public function addItem($item): self; // @phpcs:ignore Squiz.Commenting.FunctionComment.TypeHintMissing

    /**
     * Remove an item from the batch.
     *
     * @param TGatedBatchItem $item The item to remove.
     * @return $this
     */
    public function removeItem($item): self; // @phpcs:ignore Squiz.Commenting.FunctionComment.TypeHintMissing

    /**
     * Remove the items that match the given callback.
     *
     * @param callable(TGatedBatchItem):boolean $callback The callback to use to filter the items.
     * @return $this
     */
    public function removeItems(callable $callback): self;



    /**
     * Count the items in the batch.
     *
     * @return integer
     */
    public function count(): int;

    /**
     * Check if the batch is empty.
     *
     * @return boolean
     */
    public function isEmpty(): bool;
}
