<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Interfaces\Documents;

use DigitalAnomaly\AlteredLogic\Documents\DocResultSet;
use DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatchItem;
use DigitalAnomaly\AlteredLogic\Exceptions\RegistryException;
use DigitalAnomaly\AlteredLogic\Exceptions\ResourceException;

/**
 * Interface for document-searchers.
 */
interface DocSearcherInterface
{
    /**
     * Create the necessary resources / tables etc.
     *
     * @return void
     * @throws RegistryException If the embed model profile is not found.
     */
    public function initialise(): void;



    /**
     * Get the embed model profile this doc-searcher uses (if any).
     *
     * @return string|null
     */
    public function getEmbedModelProfile(): ?string;



    /**
     * Store resolved searchables.
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @param DocSearchableGatedBatchItem[] $items The items to save.
     * @return void
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function storeSearchables(array $items): void;

    /**
     * Remove searchable records from a document - of type $type if specified.
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @param string      $category   The category to remove searchables from.
     * @param string      $identifier The document's identifier.
     * @param string|null $type       The type of searchable record to remove (or null to remove all).
     * @return void
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function removeDocumentSearchables(string $category, string $identifier, ?string $type = null): void;

    /**
     * Remove all searchable records from a category - of type $type if specified.
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @param string      $category The category to remove searchables from.
     * @param string|null $type     The type of searchable record to remove (or null to remove all).
     * @return void
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function removeSearchablesFromCategory(string $category, ?string $type = null): void;

    /**
     * Remove ALL searchable records (regardless of category).
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @return void
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function removeAllSearchables(): void;



    /**
     * Search for documents.
     *
     * - Each document can have:
     *   - multiple "searchables", and those searchables span across multiple "types"
     *     - e.g. document_id 1 has searchables "car" and "fast car" of type "classification",
     *            and "red" of type "colour"
     *   - documents exist in a category
     * - This searches by comparing the stored data to $source, within the given $categories and $types.
     * - One or more $categories will be given:
     *   - The results must only include documents that exist in one of the given $categories
     * - Zero or more $types may be given:
     *   - If some $types are present, the search must only include documents that have searchables of those $types
     *   - If none are present (empty array), the search must include searchables from all $types
     * - The results must contain unique documents (unique by document_id):
     *   - i.e. no duplicates when a document has multiple searchables or types
     * - A ResourceException must be thrown if the resources / tables don't exist (i.e. haven't been initialised).
     *
     * @param string[]     $categories The categories to search in.
     * @param string[]     $types      The types of searchables to search against (searches all types by default).
     * @param string       $source     The search input.
     * @param integer      $limit      The maximum number of results to return.
     * @param integer|null $debugLevel The debug level to use: 0 = off, 1 = basic, 2 = verbose, null = use the default.
     * @return DocResultSet
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function search(
        array $categories,
        array $types,
        string $source,
        int $limit = 20,
        ?int $debugLevel = null,
    ): DocResultSet;
}
