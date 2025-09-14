<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\GatedBatch\Batch;

use ArrayIterator;
use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Item\GatedBatchItemInterface;
use IteratorAggregate;
use Traversable;

/**
 * Represents a number of document searchables that we're resolving the details for, ready to save for searching
 * against later.
 *
 * The search details aren't known to begin with (e.g. its embedding vector), but they'll be added as they're resolved.
 *
 * @template TGatedBatch of GatedBatchInterface
 * @template TGatedBatchItem of GatedBatchItemInterface
 * @implements GatedBatchInterface<TGatedBatch,TGatedBatchItem>
 * @implements IteratorAggregate<integer,TGatedBatchItem>
 */
abstract class AbstractGatedBatch implements GatedBatchInterface, IteratorAggregate
{
    /** @var array<integer,TGatedBatchItem> The items in the batch. */
    public private(set) array $items = [];





    /**
     * Get an iterator for the items.
     *
     * @return Traversable<integer,TGatedBatchItem>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Get the items in the batch.
     *
     * @return array<integer,TGatedBatchItem>
     */
    public function getItems(): array
    {
        return $this->items;
    }





    /**
     * Add an item to the batch.
     *
     * @param TGatedBatchItem $item The item to add.
     * @return $this
     */
    public function addItem($item): static // @phpcs:ignore Squiz.Commenting.FunctionComment.TypeHintMissing
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Replace the items in the batch.
     *
     * @param array<integer,TGatedBatchItem> $items The items to replace the current items with.
     * @return $this
     */
    protected function replaceItems(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Remove an item from the batch.
     *
     * @param TGatedBatchItem $item The item to remove.
     * @return $this
     */
    public function removeItem($item): static // @phpcs:ignore Squiz.Commenting.FunctionComment.TypeHintMissing
    {
        $callback = fn($tempItem) => $tempItem !== $item;

        $this->items = \array_filter($this->items, $callback);

        return $this;
    }

    /**
     * Remove the items that match the given callback.
     *
     * @param callable(TGatedBatchItem):boolean $callback The callback to use to filter the items.
     * @return $this
     */
    public function removeItems(callable $callback): static
    {
        $this->items = \array_filter(
            $this->items,
            fn($item) => !$callback($item),
        );

        return $this;
    }



    /**
     * Count the items in the batch.
     *
     * @return integer
     */
    public function count(): int
    {
        return \count($this->items);
    }

    /**
     * Check if the batch is empty.
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        return \count($this->items) === 0;
    }
}
