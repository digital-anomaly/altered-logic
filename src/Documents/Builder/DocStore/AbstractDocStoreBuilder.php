<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents\Builder\DocStore;

use DigitalAnomaly\AlteredLogic\Documents\Document;
use DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableExecutor;
use DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatch;
use DigitalAnomaly\AlteredLogic\Embed\EmbedFaker;
use DigitalAnomaly\AlteredLogic\Exceptions\DocumentException;
use DigitalAnomaly\AlteredLogic\Exceptions\RegistryException;
use DigitalAnomaly\AlteredLogic\Exceptions\ResourceException;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocSearcherInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocStoreInterface;
use DigitalAnomaly\AlteredLogic\Profiles\DocumentProfile;
use DigitalAnomaly\AlteredLogic\Registry\Registry;
use DigitalAnomaly\AlteredLogic\Support\DebugLevelHelper;
use DigitalAnomaly\AlteredLogic\Support\RetryHelper;

/**
 * Builder for the creation and storage of documents.
 *
 * AI instructions (Static Entry Pattern): This is the "enterable" class, "entered" by the DocStore class.
 */
class AbstractDocStoreBuilder
{
    /** @var boolean Whether the documents are being created in a deferred manner or not. */
    protected bool $isDeferred = false;



    /** @var string|null The document profile to use. */
    private ?string $documentProfile = null;

    /** @var string[] The doc-searchers to store searchables in. */
    private array $docSearchers = [];

    /** @var string|null The embed cache profile to use. */
    private ?string $embedCacheProfile = null;

    /** @var EmbedFaker|null The faker to use when generating embeddings. */
    private ?EmbedFaker $embedFaker = null;



    /** @var string The category to use. */
    private string $category = '';

    /** @var string The document identifier to use. */
    private string $identifier = '';

    /** @var boolean Whether the forDocument() method was called. */
    private bool $forDocument = false;

    /** @var boolean Whether the forCategory() method was called. */
    private bool $forCategory = false;



    /** @var integer|null The debug level to use: 0 = off, 1 = basic, 2 = verbose, null = use the default. */
    private ?int $debugLevel = null { set(?int $debugLevel) => DebugLevelHelper::normaliseLevel($debugLevel); } // @phpcs:ignore





    /**
     * Specify the DocumentProfile to use.
     *
     * @param string|null $profile The profile to use.
     * @return $this
     */
    public function useProfile(?string $profile): static
    {
        $this->documentProfile = $profile;

        return $this;
    }

    /**
     * Specify the DocSearcher to store searchables in - it must be associated to the DocumentProfile.
     *
     * @param string $docSearcher The doc-searcher to use.
     * @return $this
     */
    public function useSearcher(string $docSearcher): static
    {
        $this->docSearchers = [$docSearcher];

        return $this;
    }

    /**
     * Specify the DocSearchers to store searchables in - they must be associated to the DocumentProfile.
     *
     * @param string[] $docSearchers The doc-searchers to use.
     * @return $this
     */
    public function useSearchers(array $docSearchers): static
    {
        $this->docSearchers = \array_values($docSearchers);

        return $this;
    }





    /**
     * Specify the embed cache profile to use.
     *
     * @param string|null $cacheProfile The cache profile to use.
     * @return $this
     */
    public function useEmbedCacheProfile(?string $cacheProfile): static
    {
        $this->embedCacheProfile = $cacheProfile;

        return $this;
    }





    /**
     * Specify the faker to use when generating embeddings.
     *
     * @param EmbedFaker|null $faker The faker to use.
     * @return $this
     */
    public function useFaker(?EmbedFaker $faker): static
    {
        $this->embedFaker = $faker;

        return $this;
    }





    /**
     * Set the debug level to use: 0 = off, 1 = basic, 2 = verbose, null = use the default.
     *
     * @param integer|null $level The debug level to use: 0 = off, 1 = basic, 2 = verbose, null = use the default.
     * @return $this
     */
    public function debugLevel(?int $level = 1): static
    {
        $this->debugLevel = $level;

        return $this;
    }





    /**
     * Specify a document to interact with.
     *
     * @param string         $category   The category to use.
     * @param string|integer $identifier The document identifier to use.
     * @return $this
     * @throws DocumentException If the category or identifier is an empty string.
     */
    public function forDocument(string $category, string|int $identifier): static
    {
        self::validateCategory($category);

        $identifier = (string) $identifier;
        self::validateIdentifier($identifier);

        $this->category = $category;
        $this->identifier = $identifier;

        $this->forDocument = true;
        $this->forCategory = false;

        return $this;
    }

    /**
     * Specify a category to interact with.
     *
     * @param string $category The category to use.
     * @return $this
     * @throws DocumentException If the category is an empty string.
     */
    public function forCategory(string $category): static
    {
        self::validateCategory($category);

        $this->category = $category;
        $this->identifier = '';

        $this->forDocument = false;
        $this->forCategory = true;

        return $this;
    }





    /**
     * Retrieve a document from the system.
     *
     * @return Document|null
     * @throws DocumentException If forDocument() wasn't called beforehand.
     * @throws ResourceException If the necessary resources / tables don't exist and can't be created.
     */
    public function getDocument(): ?Document
    {
        $this->ensureForDocWasCalled(__FUNCTION__);

        $work = fn() => $this->getDocStore()->getDocument($this->category, $this->identifier);

        /** @var Document|null $return */
        $return = RetryHelper::docStoreTry($work, $this->getDocStore());

        return $return;
    }

    /**
     * Get a document's id, or create it if it doesn't exist.
     *
     * @return integer|null
     */
    private function getDocumentIdOrCreateNew(): ?int
    {
        $document = $this->getDocument();
        $documentId = $document?->documentId;

        if ($documentId !== null) {
            return $documentId;
        }

        // todo - consider race condition here, where the document is created between the getDocument() call above
        //        and the addMeta() call below (in which case ->addMeta() will return a document id of null)

        // this part doesn't need to be in a ->try() call, because the
        // resource/table would have been created above by ->getDocument()

        // equivalent to $this->addMeta([]), but will use the returned document id
        return $this->getDocStore()->addMeta($this->category, $this->identifier, []);
    }





    /**
     * Add metadata to a document. Updates the metadata if it already exists.
     *
     * @param array<string,mixed> $metadata The metadata to add to the document.
     * @return $this
     * @throws DocumentException If forDocument() wasn't called beforehand.
     * @throws ResourceException If the necessary resources / tables don't exist and can't be created.
     */
    public function addDocMeta(array $metadata = []): static
    {
        $this->ensureForDocWasCalled(__FUNCTION__);

        $work = fn() => $this->getDocStore()->addMeta(
            $this->category,
            $this->identifier,
            self::normaliseMetadata($metadata),
        );

        RetryHelper::docStoreTry($work, $this->getDocStore());

        return $this;
    }

    /**
     * Remove specific metadata keys from a document. Leaves remaining metadata intact.
     *
     * @param string[] $keys The metadata keys to remove.
     * @return $this
     * @throws DocumentException If forDocument() wasn't called beforehand.
     * @throws ResourceException If the necessary resources / tables don't exist and can't be created.
     */
    public function removeDocMeta(array $keys): static
    {
        $this->ensureForDocWasCalled(__FUNCTION__);

        if (\count($keys) === 0) {
            return $this;
        }

        // make sure the keys are strings
        foreach ($keys as $index => $key) {
            $keys[$index] = (string) $key; // @phpstan-ignore-line
        }
        $keys = \array_values(\array_unique($keys));

        $work = fn() => $this->getDocStore()->removeMeta(
            $this->category,
            $this->identifier,
            $keys,
        );

        RetryHelper::docStoreTry($work, $this->getDocStore());

        return $this;
    }

    /**
     * Replace a document's metadata entirely. Will create a new document if it doesn't exist.
     *
     * @param array<string,mixed> $metadata The new metadata to associate with the document.
     * @return $this
     * @throws DocumentException If forDocument() wasn't called beforehand.
     * @throws ResourceException If the necessary resources / tables don't exist and can't be created.
     */
    public function replaceDocMeta(array $metadata): static
    {
        $this->ensureForDocWasCalled(__FUNCTION__);

        $work = fn() => $this->getDocStore()->replaceMeta(
            $this->category,
            $this->identifier,
            self::normaliseMetadata($metadata),
        );

        RetryHelper::docStoreTry($work, $this->getDocStore());

        return $this;
    }





    /**
     * Add a searchable to a document.
     *
     * @param string $type   The type, used to identify the searchable.
     * @param mixed  $source The searchable to add - if not a string, it will be encoded as JSON.
     * @return $this
     * @throws DocumentException If forDocument() wasn't called beforehand.
     * @throws ResourceException If the necessary resources / tables don't exist and can't be created.
     */
    public function addSearchable(string $type, mixed $source): static
    {
        return $this->_addSearchables($type, [$source], __FUNCTION__);
    }

    /**
     * Add searchables to a document.
     *
     * @param string       $type    The type, used to identify the searchables.
     * @param array<mixed> $sources The searchables to add - those that aren't strings will be encoded as JSON.
     * @return $this
     * @throws DocumentException If forDocument() wasn't called beforehand.
     * @throws ResourceException If the necessary resources / tables don't exist and can't be created.
     */
    public function addSearchables(string $type, array $sources): static
    {
        return $this->_addSearchables($type, $sources, __FUNCTION__);
    }

    /**
     * Add searchables to a document.
     *
     * @param string       $type    The type, used to identify the searchables.
     * @param array<mixed> $sources The searchables to add - those that aren't strings will be encoded as JSON.
     * @param string       $method  The method that was called (for exception messages).
     * @return $this
     */
    private function _addSearchables(string $type, array $sources, string $method): static
    {
        $this->ensureForDocWasCalled($method);

        $documentId = $this->getDocumentIdOrCreateNew();
        if ($documentId === null) {
            return $this;
        }

        foreach ($this->getSpecifiedDocSearchers() as $docSearcher) {

            $gatedBatch = $this->getCurrentDocSearchableGatedBatch($docSearcher);

            $gatedBatch->addItemsToResolve($documentId, $this->category, $this->identifier, $type, $sources);

            DocSearchableExecutor::processBatch($gatedBatch, !$this->isDeferred);

            $gatedBatch->purgeItemsWithResolvedEmbedding();
        }

        return $this;
    }

    /**
     * Flush doc-searchables - Process all outstanding doc-searchables (across all embedding models).
     *
     * @return void
     */
    protected function _flush(): void
    {
        foreach (Registry::getAllDeferredDocSearchableGatedBatches() as $gatedBatch) {

            DocSearchableExecutor::processBatch($gatedBatch, true);

            $gatedBatch->purgeItemsWithResolvedEmbedding();
        }
    }





    /**
     * Remove a particular type of searchable from a document, or all of a category's documents.
     *
     * @param string $type The type of searchable to remove.
     * @return $this
     * @throws DocumentException If neither forDocument() or forCategory() were called beforehand.
     * @throws ResourceException If the necessary resources / tables don't exist and can't be created.
     */
    public function removeSearchableType(string $type): static
    {
        $this->ensureForDocOrForCatWasCalled(__FUNCTION__);

        foreach ($this->getSpecifiedDocSearchers() as $docSearcher) {

            $work = fn() => $this->forDocument
                ? $docSearcher->removeDocumentSearchables($this->category, $this->identifier, $type)
                : $docSearcher->removeSearchablesFromCategory($this->category, $type);

            RetryHelper::docSearcherTry($work, $docSearcher);
        }

        return $this;
    }

    /**
     * Remove all searchables from a document, or all of a category's documents.
     *
     * @return $this
     * @throws DocumentException If neither forDocument() or forCategory() were called beforehand.
     * @throws ResourceException If the necessary resources / tables don't exist and can't be created.
     */
    public function removeAllSearchables(): static
    {
        $this->ensureForDocOrForCatWasCalled(__FUNCTION__);

        foreach ($this->getSpecifiedDocSearchers() as $docSearcher) {

            $work = fn() => $this->forDocument
                ? $docSearcher->removeDocumentSearchables($this->category, $this->identifier)
                : $docSearcher->removeSearchablesFromCategory($this->category);

            RetryHelper::docSearcherTry($work, $docSearcher);
        }

        return $this;
    }





    /**
     * Remove a document.
     *
     * @param string         $category   The category the document is in.
     * @param string|integer $identifier The document's identifier.
     * @return $this
     * @throws DocumentException If forDocument() or forCategory() were called beforehand, or the identifier is an empty
     *                           string.
     * @throws ResourceException If the necessary resources / tables don't exist and can't be created.
     */
    public function removeDocument(string $category, string|int $identifier): static
    {
        $this->ensureNeitherForDocOrForCatWasCalled(__FUNCTION__);

        $identifier = (string) $identifier;
        self::validateIdentifier($identifier);

        // remove the document's searchables
        foreach ($this->getAllDocSearchers() as $docSearcher) {

            $work = fn() => $docSearcher->removeDocumentSearchables($category, $identifier);
            RetryHelper::docSearcherTry($work, $docSearcher);
        }

        // remove the document
        $work = fn() => $this->getDocStore()->removeDocument($category, $identifier);
        RetryHelper::docStoreTry($work, $this->getDocStore());

        return $this;
    }

    /**
     * Remove all documents from a category.
     *
     * Warning: This will remove all documents from the specified category.
     *
     * @param string $category The category to remove documents from.
     * @return $this
     * @throws DocumentException If forDocument() or forCategory() were called beforehand.
     * @throws ResourceException If the necessary resources / tables don't exist and can't be created.
     */
    public function purgeCategoryDocuments(string $category): static
    {
        $this->ensureNeitherForDocOrForCatWasCalled(__FUNCTION__);

        // remove all searchables from the category
        foreach ($this->getAllDocSearchers() as $docSearcher) {

            $work = fn() => $docSearcher->removeSearchablesFromCategory($category);
            RetryHelper::docSearcherTry($work, $docSearcher);
        }

        // remove all documents from the category
        $work = fn() => $this->getDocStore()->purgeCategoryDocuments($category);
        RetryHelper::docStoreTry($work, $this->getDocStore());

        return $this;
    }

    /**
     * Remove ALL documents (regardless of category).
     *
     * Warning: This will remove all documents, from every category.
     *
     * @return $this
     * @throws DocumentException If forDocument() or forCategory() were called beforehand.
     * @throws ResourceException If the necessary resources / tables don't exist and can't be created.
     */
    public function purgeAllDocuments(): static
    {
        $this->ensureNeitherForDocOrForCatWasCalled(__FUNCTION__);

        // remove all searchables
        foreach ($this->getAllDocSearchers() as $docSearcher) {

            $work = fn() => $docSearcher->removeAllSearchables();
            RetryHelper::docSearcherTry($work, $docSearcher);
        }

        // remove all documents
        $work = fn() => $this->getDocStore()->purgeAllDocuments();
        RetryHelper::docStoreTry($work, $this->getDocStore());

        return $this;
    }











    /**
     * Get the doc-searchable gated batch to use.
     *
     * @param DocSearcherInterface $docSearcher The doc-searcher to get the gated batch for.
     * @return DocSearchableGatedBatch
     */
    private function getCurrentDocSearchableGatedBatch(DocSearcherInterface $docSearcher): DocSearchableGatedBatch
    {
        $documentProfile = Registry::documentProfiles()->getOrThrow((string) $this->documentProfile);

        $embedModelProfileName = $docSearcher->getEmbedModelProfile();
        $embedModelProfile = Registry::embedModelProfiles()->get($embedModelProfileName, allowEmpty: true);

        $embedCacheProfile = $embedModelProfile !== null
            ? Registry::embedCacheProfiles()->get($this->embedCacheProfile ?? '', allowEmpty: true)
            : null;

        if ($this->debugLevel !== null) {
            $docDebugLevel = $this->debugLevel;
        } elseif (Registry::docConfig()->debugLevel !== null) {
            $docDebugLevel = Registry::docConfig()->debugLevel;
        } else {
            $docDebugLevel = 0;
        }

        if ($this->debugLevel !== null) {
            $embedDebugLevel = $this->debugLevel;
        } elseif (Registry::embedConfig()->debugLevel !== null) {
            $embedDebugLevel = Registry::embedConfig()->debugLevel;
        } elseif (Registry::docConfig()->debugLevel !== null) {
            $embedDebugLevel = Registry::docConfig()->debugLevel;
        } else {
            $embedDebugLevel = 0;
        }

        return Registry::getDocSearchableGatedBatch(
            $this->isDeferred,
            $documentProfile,
            $docSearcher,
            $embedModelProfile,
            $embedCacheProfile,
            $this->embedFaker,
            $docDebugLevel,
            $embedDebugLevel,
        );
    }



    /**
     * Resolve the document profile to use.
     *
     * @return DocumentProfile
     * @throws RegistryException If the document profile is not found.
     */
    private function getDocumentProfile(): DocumentProfile
    {
        return Registry::documentProfiles()->getOrThrow($this->documentProfile ?? '');
    }

    /**
     * Get the DocStore to use.
     *
     * @return DocStoreInterface
     */
    private function getDocStore(): DocStoreInterface
    {
        return $this->getDocumentProfile()->getDocStore();
    }

    /**
     * Get all the registered search stores.
     *
     * @return array<string,DocSearcherInterface>
     */
    private function getAllDocSearchers(): array
    {
        return $this->getDocumentProfile()->getDocSearchers();
    }

    /**
     * Get the search stores that have been specified by the caller.
     *
     * Will return all if none have been specified.
     *
     * @return array<string,DocSearcherInterface>
     */
    private function getSpecifiedDocSearchers(): array
    {
        $docSearchers = [];
        foreach ($this->docSearchers as $docSearcher) {
            $docSearchers[$docSearcher] = $this->getDocumentProfile()->getDocSearcher($docSearcher);
        }

        return \count($docSearchers) > 0
            ? $docSearchers
            : $this->getAllDocSearchers();
    }





    /**
     * Normalise the metadata (turn integer keys into strings).
     *
     * @param array<integer|string,mixed> $metadata The metadata to normalise.
     * @return array<string,mixed>
     */
    private static function normaliseMetadata(array $metadata): array
    {
        $normalisedMetadata = [];
        foreach ($metadata as $key => $value) {
            $normalisedMetadata[(string) $key] = $value;
        }

        return $normalisedMetadata;
    }





    /**
     * Validate that the category is valid to use.
     *
     * @param string $category The category to check.
     * @return void
     * @throws DocumentException When the category is invalid.
     */
    private static function validateCategory(string $category): void
    {
        if ($category === '') {
            throw DocumentException::categoryCannotBeAnEmptyString();
        }
    }

    /**
     * Validate the identifier is valid to use.
     *
     * @param string|integer $identifier The identifier to check.
     * @return void
     * @throws DocumentException When the identifier is invalid.
     */
    private static function validateIdentifier(string|int $identifier): void
    {
        if ($identifier === '') {
            throw DocumentException::identifierCannotBeAnEmptyString();
        }
    }



    /**
     * Throw an exception if forDocument() wasn't called first.
     *
     * @param string $method The method that was called (for exception messages).
     * @return void
     * @throws DocumentException If forDocument() wasn't called beforehand.
     */
    private function ensureForDocWasCalled(string $method): void
    {
        if ($this->forDocument) {
            return;
        }

        throw DocumentException::callForDocFirst($method);
    }

    /**
     * Throw an exception if neither forDocument() or forCategory() were called first.
     *
     * @param string $method The method that was called (for exception messages).
     * @return void
     * @throws DocumentException If neither forDocument() or forCategory() were called beforehand.
     */
    private function ensureForDocOrForCatWasCalled(string $method): void
    {
        if ($this->forDocument) {
            return;
        }
        if ($this->forCategory) {
            return;
        }

        throw DocumentException::callForDocOrForCatFirst($method);
    }

    /**
     * Throw an exception if forDocument() or forCategory() were called first.
     *
     * @param string $method The method that was called (for exception messages).
     * @return void
     * @throws DocumentException If forDocument() or forCategory() were called beforehand.
     */
    private function ensureNeitherForDocOrForCatWasCalled(string $method): void
    {
        if ($this->forDocument) {
            throw DocumentException::dontCallForDocOrForCatFirst($method);
        }
        if ($this->forCategory) {
            throw DocumentException::dontCallForDocOrForCatFirst($method);
        }
    }
}
