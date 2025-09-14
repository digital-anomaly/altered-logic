<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents\Builder\DocSearch;

use DigitalAnomaly\AlteredLogic\Documents\DocResultSet;
use DigitalAnomaly\AlteredLogic\Documents\Document;
use DigitalAnomaly\AlteredLogic\Exceptions\DocumentException;
use DigitalAnomaly\AlteredLogic\Exceptions\RegistryException;
use DigitalAnomaly\AlteredLogic\Exceptions\ResourceException;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocSearcherInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocStoreInterface;
use DigitalAnomaly\AlteredLogic\Profiles\DocumentProfile;
use DigitalAnomaly\AlteredLogic\Registry\Registry;
use DigitalAnomaly\AlteredLogic\Support\DebugLevelHelper;
use DigitalAnomaly\AlteredLogic\Support\EmbedHelper;
use DigitalAnomaly\AlteredLogic\Support\RetryHelper;

/**
 * Builder to search for documents.
 *
 * AI instructions (Static Entry Pattern): This is the "enterable" class, "entered" by the DocSearch class.
 */
final class DocSearchBuilder
{
    /** @var string|null The document profile to use. */
    private ?string $documentProfile = null;

    /** @var string[] The doc-searchers to use. */
    private array $docSearchers = [];



    /** @var integer|null The debug level to use: 0 = off, 1 = basic, 2 = verbose, null = use the default. */
    private ?int $debugLevel = null { set(?int $value) => DebugLevelHelper::normaliseLevel($value); } // @phpcs:ignore



    // /** @var string[] The categories to search in. */
    // private array $categories = [];



    /** @var boolean Whether to add metadata to the DocResults objects. */
    private bool $addMetadata = true;





    /**
     * Specify the DocumentProfile to use.
     *
     * @param string|null $profile The profile to use.
     * @return self
     */
    public function useProfile(?string $profile): self
    {
        $this->documentProfile = $profile;

        return $this;
    }

    /**
     * Specify the DocSearcher to use.
     *
     * @param string $docSearcher The doc-searcher to use.
     * @return self
     */
    public function useSearcher(string $docSearcher): self
    {
        $this->docSearchers = [$docSearcher];

        return $this;
    }





    /**
     * Specify the debug level.
     *
     * @param integer|null $level The debug level to use: 0 = off, 1 = basic, 2 = verbose, null = use the default.
     * @return self
     */
    public function debugLevel(?int $level = 1): self
    {
        $this->debugLevel = $level;

        return $this;
    }





    /**
     * Save some time if document metadata isn't needed.
     *
     * @return self
     */
    public function skipMetadata(): self
    {
        $this->addMetadata = false;

        return $this;
    }





    /**
     * Search for documents.
     *
     * @param string|string[] $category The category/s to search in.
     * @param string          $source   The search input.
     * @param integer         $limit    The maximum number of results to return.
     * @param string|string[] $type     The type/s of searchables to search against (searches all types by default).
     * @return DocResultSet
     * @throws DocumentException If the category is not set.
     * @throws ResourceException If the necessary resources / tables don't exist and can't be created.
     */
    public function search(
        string|array $category,
        string $source,
        int $limit = 20,
        string|array $type = [],
    ): DocResultSet {

        $categories = \is_array($category)
            ? $category
            : [$category];

        $types = \is_array($type)
            ? $type
            : [$type];

        if (\count($categories) === 0) {
            throw DocumentException::specifyACategory(__FUNCTION__);
        }

        foreach ($categories as $tempCategory) {
            if (\mb_strlen($tempCategory) === 0) {
                throw DocumentException::specifyACategory(__FUNCTION__);
            }
        }

        $docSearchers = $this->getSpecifiedDocSearchers();

        if (\count($docSearchers) > 1) {
            throw DocumentException::multipleSearchersNotAllowed(\array_keys($docSearchers));
        }

        $docResultSets = [];
        foreach ($docSearchers as $docSearcher) {
            $work = fn() => $docSearcher->search(
                $categories,
                $types,
                EmbedHelper::normaliseSource($source),
                $limit,
                $this->debugLevel,
            );
            $docResultSets[] = RetryHelper::docSearcherTry($work, $docSearcher);
        }

        $docResultSets = $this->addMetadataToDocResultSets($docResultSets);

        // do this later if desired:
        // todo - perform multiple searches ->search($searchTerm1, $searchTerm2)
        //        - work out how to combine the results from multiple searches (from the same doc-searcher)
        //      - allow searches against multiple (probably different types of) doc-searchers
        //        - work out how to re-rank the results automatically

        return \reset($docResultSets);
    }

    /**
     * Add metadata to the DocResultSet objects.
     *
     * @param DocResultSet[] $docResultSets The DocResultSet objects to add metadata to.
     * @return DocResultSet[]
     */
    private function addMetadataToDocResultSets(array $docResultSets): array
    {
        if (!$this->addMetadata) {
            return $docResultSets;
        }

        $documentIds = [];
        foreach ($docResultSets as $docResultSet) {
            $documentIds = \array_merge($documentIds, $docResultSet->getDocumentIds());
        }

        $documentMetadata = $this->loadDocumentMetadata($documentIds);

        foreach ($docResultSets as $index => $docResultSet) {
            $docResultSets[$index] = $this->addMetadataToDocResultSet($docResultSet, $documentMetadata);
        }

        return $docResultSets;
    }

    /**
     * Get the metadata for documents based on their ids.
     *
     * @param integer[] $documentIds The document's ids.
     * @return array<integer,array<string,mixed>>
     */
    private function loadDocumentMetadata(array $documentIds): array
    {
        if (\count($documentIds) === 0) {
            return [];
        }

        $docMetaJson = $this->getDocStore()->getDocumentMetadataJsonById($documentIds);

        $docMetadata = [];
        foreach ($docMetaJson as $documentId => $metadata) {

            $metadata = \json_decode($metadata, true) ?? [];

            /** @var array<string,mixed> $metadata */
            $metadata = \is_array($metadata)
                ? $metadata
                : [];

            $docMetadata[$documentId] = $metadata;
        }

        return $docMetadata;
    }

    /**
     * Add metadata to a Documents in a DocResultSet object.
     *
     * @param DocResultSet                       $docResultSet     The DocResultSet object to add metadata to.
     * @param array<integer,array<string,mixed>> $documentMetadata The metadata to add.
     * @return DocResultSet
     */
    private function addMetadataToDocResultSet(DocResultSet $docResultSet, array $documentMetadata): DocResultSet
    {
        foreach ($docResultSet->all() as $index => $document) {
            $docResultSet[$index] = new Document(
                $document->documentId,
                $document->category,
                $document->identifier,
                $documentMetadata[$document->documentId] ?? [],
                $document->sourceContent,
                $document->sourceType,
            );
        }

        return $docResultSet;
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
     * Get the doc-searchers that have been specified by the caller.
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
}
