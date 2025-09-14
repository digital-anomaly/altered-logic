<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\GatedBatch\Item;

use UnitEnum;

/**
 * Trait to add to GatedBatchItemInterface classes.
 *
 * @template TGatedBatchItem of GatedBatchItemInterface
 */
trait GatedBatchItemTrait
{
    /** @var array<string|UnitEnum> The tags that have been added. */
    private array $tags = [];



    /**
     * Add a tag to the item.
     *
     * @param string|UnitEnum $tag The tag to add.
     * @return TGatedBatchItem
     */
    public function addTag(string|UnitEnum $tag): GatedBatchItemInterface
    {
        if (!\in_array($tag, $this->tags, true)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    /**
     * Check if the item has the given tag.
     *
     * @param string|UnitEnum $tag The tag to check for.
     * @return boolean
     */
    public function hasTag(string|UnitEnum $tag): bool
    {
        return \in_array($tag, $this->tags, true);
    }
}
