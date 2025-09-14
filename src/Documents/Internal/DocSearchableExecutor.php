<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents\Internal;

use DigitalAnomaly\AlteredLogic\Embed\Internal\EmbedExecutor;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\EmbedGatedBatchInterface;
use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Batch\AbstractGatedBatch;
use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Batch\GatedBatchInterface;
use DigitalAnomaly\AlteredLogic\Support\RetryHelper;

/**
 * Takes an DocSearchableGatedBatch and resolves the embedding vectors for it.
 */
final class DocSearchableExecutor
{
    /**
     * Process the batch.
     *
     * @param AbstractGatedBatch<DocSearchableGatedBatch,DocSearchableGatedBatchItem>&GatedBatchInterface&EmbedGatedBatchInterface $gatedBatch The batch to process.
     * @param boolean                                      $force      Whether or not to force the batch to run,
     *                                                                 regardless of the number of items.
     * @return void
     */
    public static function processBatch(
        AbstractGatedBatch&GatedBatchInterface&EmbedGatedBatchInterface $gatedBatch,
        bool $force,
    ): void {

        if ($gatedBatch->isEmpty()) {
            return;
        }

        if ($gatedBatch instanceof EmbedGatedBatchInterface) {

            EmbedExecutor::processBatch($gatedBatch, $force);

            $batchItemsToSave = [];
            foreach ($gatedBatch->getItems() as $batchItem) {
                if ($batchItem->getPendingEmbedding()->vector !== null) {
                    $batchItemsToSave[] = $batchItem;
                }
            }

            if (\count($batchItemsToSave) > 0) {

                $work = fn() => $gatedBatch->docSearcher->storeSearchables($batchItemsToSave);

                RetryHelper::docSearcherTry($work, $gatedBatch->docSearcher);
            }
        }
    }
}
