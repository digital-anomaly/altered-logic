<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatch;

/**
 * Represents a doc-searchable, as it goes through the process of being resolved.
 */
final class DocSearchable
{
    /**
     * Constructor.
     *
     * @param integer $documentId The document's id.
     * @param string  $category   The category the document belongs to.
     * @param string  $identifier The document's identifier.
     * @param string  $type       The type, used to classify the searchable.
     * @param string  $source     The source of the searchable.
     */
    public function __construct(
        public private(set) int $documentId,
        public private(set) string $category,
        public private(set) string $identifier,
        public private(set) string $type,
        public private(set) string $source,
    ) {}
}
