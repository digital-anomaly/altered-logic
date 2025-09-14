<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents\Internal;

use DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatch\DocSearchable;
use DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatch\DocSearchableGatedBatchInterface;
use DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatch\DocSearchableGatedBatchTrait;
use DigitalAnomaly\AlteredLogic\Embed\EmbedFaker;
use DigitalAnomaly\AlteredLogic\Embed\Internal\EmbedGatedBatchItem;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\EmbedGatedBatchInterface;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\EmbedGatedBatchTrait;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\PendingEmbedding;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocSearcherInterface;
use DigitalAnomaly\AlteredLogic\Profiles\DocumentProfile;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedCacheProfile;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedModelProfile;
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
     * @param DocumentProfile                            $documentProfile   The document profile to use.
     * @param DocSearcherInterface                       $docSearcher       The doc-searcher to store searchables with.
     * @param EmbedModelProfile|null                     $embedModelProfile The model profile to use.
     * @param EmbedCacheProfile|null                     $embedCacheProfile The cache profile to use.
     * @param EmbedFaker|null                            $embedFaker        The faker to use when generating embeddings.
     * @param integer                                    $docDebugLevel     The doc debug level to use.
     * @param integer                                    $embedDebugLevel   The embed debug level to use.
     * @param array<integer,DocSearchableGatedBatchItem> $items             The items to add to the batch.
     */
    public function __construct(
        DocumentProfile $documentProfile,
        DocSearcherInterface $docSearcher,
        ?EmbedModelProfile $embedModelProfile,
        ?EmbedCacheProfile $embedCacheProfile,
        ?EmbedFaker $embedFaker,
        int $docDebugLevel,
        int $embedDebugLevel,
        array $items = [],
    ) {
        $this->documentProfile = $documentProfile;
        $this->docSearcher = $docSearcher;
        $this->docDebugLevel = $docDebugLevel;

        $this->embedModelProfile = $embedModelProfile;
        $this->embedCacheProfile = $embedCacheProfile;
        $this->embedFaker = $embedFaker;
        $this->debugLevel = $embedDebugLevel;

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
