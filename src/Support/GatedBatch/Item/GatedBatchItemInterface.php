<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\GatedBatch\Item;

use UnitEnum;

/**
 * Represents a batch item which is to be processed. Its steps are tracked so it can proceed through a process, one step
 * at a time.
 */
interface GatedBatchItemInterface
{
    /**
     * Add a tag to the item.
     *
     * @param string|UnitEnum $tag The tag to add.
     * @return $this
     */
    public function addTag(string|UnitEnum $tag): self;

    /**
     * Check if the item has the given tag.
     *
     * @param string|UnitEnum $tag The tag to check for.
     * @return boolean
     */
    public function hasTag(string|UnitEnum $tag): bool;
}
