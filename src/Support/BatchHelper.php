<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support;

/**
 * Helper methods for working with batches.
 */
final class BatchHelper
{
    /**
     * Split an array into batches of a given size.
     *
     * @template TKey of integer|string
     * @template TValue
     * @param array<TKey,TValue> $array        The array to split into batches.
     * @param int<1,max>         $batchSize    The size of each batch.
     * @param boolean            $force        Whether to include the last batch if it's smaller than the batch size.
     * @param boolean            $preserveKeys Whether to preserve the keys of the array.
     * @return array<integer,array<TKey,TValue>>
     */
    public static function splitIntoBatches( // @phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
        array $array,
        int $batchSize,
        bool $force,
        bool $preserveKeys,
    ): array {

        $batches = \array_chunk($array, $batchSize, $preserveKeys);
        if ($force) {
            return $batches;
        }

        if ($batches !== []) {

            $lastBatch = \end($batches);

            if (\count($lastBatch) < $batchSize) {
                $batches = \array_slice($batches, 0, -1);
            }
        }

        return $batches;
    }
}
