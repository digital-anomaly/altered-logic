<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents\Internal;

use DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatch\DocSearchable;
use DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatch\DocSearchableGatedBatchInterface;
use DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatch\DocSearchableGatedBatchTrait;
use DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatch\DTOs\DocSearchableGatedBatchIdentityDTO;
use DigitalAnomaly\AlteredLogic\Embed\Internal\EmbedGatedBatchItem;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\EmbedGatedBatchInterface;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\EmbedGatedBatchTrait;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\PendingEmbedding;
use DigitalAnomaly\AlteredLogic\Support\EmbedHelper;
use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Batch\AbstractGatedBatch;
use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Status\GatedBatchStatusInterface;
use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Status\GatedBatchStatusTrait;
use DigitalAnomaly\AlteredLogic\Support\RetryHelper;

/**
 * Represents a number of document searchables that we're resolving the details for, ready to save for searching
 * against later.
 *
 * The search details aren't known to begin with (e.g. its embedding vector), but they'll be added as they're resolved.
 *
 * @extends AbstractGatedBatch<DocSearchableGatedBatch,DocSearchableGatedBatchItem>
 */
final class DocSearchableGatedBatch extends AbstractGatedBatch implements
    DocSearchableGatedBatchInterface,
    EmbedGatedBatchInterface,
    GatedBatchStatusInterface // todo - remove this?
{
    /** @use EmbedGatedBatchTrait<EmbedGatedBatchItem> */
    use EmbedGatedBatchTrait;
    /** @use DocSearchableGatedBatchTrait<DocSearchableGatedBatchItem> */
    use DocSearchableGatedBatchTrait;
    /** @use GatedBatchStatusTrait<DocSearchableGatedBatch> */
    use GatedBatchStatusTrait;



    // /** @var EmbedGatedBatch|null Tracks embeddings that need to be resolved. */
    // private ?EmbedGatedBatch $embedGatedBatch = null;



    /**
     * Create a new batch.
     *
     * @param DocSearchableGatedBatchIdentityDTO         $identity The identity of the batch.
     * @param array<integer,DocSearchableGatedBatchItem> $items    The items to add to the batch.
     */
    public function __construct(DocSearchableGatedBatchIdentityDTO $identity, array $items = [])
    {
        $this->documentProfile = $identity->documentProfile;
        $this->docSearcher = $identity->docSearcher;
        $this->docDebugLevel = $identity->docDebugLevel;

        $this->embedModelProfile = $identity->embedModelProfile;
        $this->embedCacheProfile = $identity->embedCacheProfile;
        $this->embedFaker = $identity->embedFaker;
        $this->debugLevel = $identity->embedDebugLevel;

        $this->replaceItems($items);
    }



    /**
     * Add items to the batch, ready to be resolved.
     *
     * @param integer                     $documentId The document's id.
     * @param string                      $category   The category the document belongs to.
     * @param string                      $identifier The document's identifier.
     * @param string                      $type       The type, used to classify the searchable.
     * @param array<string|integer,mixed> $sources    The searchables to add - non-string items will be encoded as JSON.
     * @return $this
     */
    public function addItemsToResolve(
        int $documentId,
        string $category,
        string $identifier,
        string $type,
        array $sources,
    ): self {

        $sources = EmbedHelper::normaliseSources($sources);

        foreach ($sources as $source) {

            $docSearchable = new DocSearchable($documentId, $category, $identifier, $type, $source);
            $pendingEmbedding = new PendingEmbedding($source);

            $batchItem = new DocSearchableGatedBatchItem($docSearchable, $pendingEmbedding);

            $this->addItem($batchItem);
        }

        return $this;
    }





    /**
     * Process the batch.
     *
     * @todo move this logic to DocSearchableExecutor
     *
     * @param boolean      $force      Whether or not to force the batch to run, regardless of the number of items.
     * @param integer|null $debugLevel The debug level to use: 0 = off, 1 = basic, 2 = verbose, null = use the default.
     * @return void
     */
    public function execute(bool $force = false, ?int $debugLevel = null): void
    {
        // resolve the searchables
        RetryHelper::docSearcherTry(
            fn() => $this->docSearcher->resolveSearchables($this, $force, $debugLevel),
            $this->docSearcher,
        );

        // store the searchables
        RetryHelper::docSearcherTry(
            fn() => $this->docSearcher->storeSearchables($this, $force),
            $this->docSearcher,
        );

        // forget the searchables that have been stored
        $this->removeCompletedItems();
    }
}
