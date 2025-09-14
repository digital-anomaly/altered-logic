<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Interfaces\Documents;

use DigitalAnomaly\AlteredLogic\Documents\Document;
use DigitalAnomaly\AlteredLogic\Exceptions\ResourceException;

/**
 * Interface for document-stores.
 */
interface DocStoreInterface
{
    /**
     * Create the necessary resources / tables etc.
     *
     * @return void
     */
    public function initialise(): void;



    /**
     * Retrieve a document from the system.
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @param string $category   The category to retrieve from.
     * @param string $identifier The document's identifier.
     * @return Document|null
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function getDocument(string $category, string $identifier): ?Document;

    /**
     * Retrieve the metadata for documents based on their ids - just return the metadata json as a string.
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @param integer[] $documentIds The document's ids.
     * @return array<integer,string>
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function getDocumentMetadataJsonById(array $documentIds): array;



    /**
     * Add metadata to a document. Updates the metadata if it already exists.
     *
     * Only returns the document id if the document was created.
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @param string              $category   The category to store in.
     * @param string              $identifier The document's identifier.
     * @param array<string,mixed> $metadata   The metadata to associate with the document.
     * @return integer|null
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function addMeta(string $category, string $identifier, array $metadata): ?int;

    /**
     * Remove specific metadata keys from a document. Leaves other metadata intact.
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @param string   $category   The category to remove from.
     * @param string   $identifier The document's identifier.
     * @param string[] $keys       The metadata keys to remove (assumes there is at least one key).
     * @return void
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function removeMeta(string $category, string $identifier, array $keys): void;

    /**
     * Replace a document's metadata entirely. Will create a new document if it doesn't exist.
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @param string              $category   The category to store in.
     * @param string              $identifier The document's identifier.
     * @param array<string,mixed> $metadata   The new metadata to associate with the document.
     * @return void
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function replaceMeta(string $category, string $identifier, array $metadata): void;



    /**
     * Remove a document.
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @param string $category   The category to remove from.
     * @param string $identifier The document's identifier.
     * @return void
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function removeDocument(string $category, string $identifier): void;

    /**
     * Remove all documents from a category.
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @param string $category The category to purge.
     * @return void
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function purgeCategoryDocuments(string $category): void;

    /**
     * Remove ALL documents (regardless of category).
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @return void
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function purgeAllDocuments(): void;
}
