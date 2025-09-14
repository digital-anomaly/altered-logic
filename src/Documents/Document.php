<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents;

/**
 * Represents a Document retrieved from the system.
 */
final class Document
{
    /**
     * @param integer             $documentId    The document's id.
     * @param string              $category      The category of the document.
     * @param string              $identifier    The unique identifier of the document.
     * @param array<string,mixed> $metadata      The document's metadata.
     * @param string|null         $sourceContent The source content that was used to find the document.
     * @param string|null         $sourceType    The type of $source.
     */
    public function __construct(
        public private(set) int $documentId,
        public private(set) string $category,
        public private(set) string $identifier,
        public private(set) array $metadata,
        public private(set) ?string $sourceContent, // todo - put into an array of another class,  containing source, type and score from the different types of Stores? That object will be null if the Document was loaded using the `DocStore` class (i.e. not via a search)
        public private(set) ?string $sourceType, // todo - put into an array of another class,  containing source, type and score from the different types of Stores? That object will be null if the Document was loaded using the `DocStore` class (i.e. not via a search)
    ) {}



    /**
     * Get a specific metadata value by key.
     *
     * @param string $key The metadata key to retrieve.
     * @return mixed The metadata value, or null if not found.
     */
    public function meta(string $key): mixed
    {
        return $this->metadata[$key] ?? null;
    }
}
