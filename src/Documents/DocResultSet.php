<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents;

use ArrayAccess;
use DigitalAnomaly\AlteredLogic\Exceptions\DocumentException;
use Countable;
use Iterator;

/**
 * Represents a set of Documents retrieved from the system.
 *
 * @template-implements \ArrayAccess<integer,Document>
 * @template-implements \Iterator<integer,Document>
 */
final class DocResultSet implements ArrayAccess, Countable, Iterator
{
    /** @var array<integer,Document> The documents. */
    private array $documents = [];



    /**
     * Constructor.
     *
     * @param Document[] $documents The documents to include.
     * @throws DocumentException When the value is not a Document instance.
     */
    public function __construct(array $documents = [])
    {
        foreach ($documents as $document) {
            if (!$document instanceof Document) {
                throw DocumentException::invalidType(Document::class);
            }
        }
        $this->documents = \array_values($documents);
    }

    /**
     * Get all documents.
     *
     * @return array<integer,Document>
     */
    public function all(): array
    {
        return $this->documents;
    }

    /**
     * Get the first document.
     *
     * @return Document|null
     */
    public function first(): ?Document
    {
        return $this->documents[0] ?? null;
    }

    /**
     * Get the last document.
     *
     * @return Document|null
     */
    public function last(): ?Document
    {
        $count = \count($this->documents);

        return $count > 0 ? $this->documents[$count - 1] : null;
    }

    /**
     * Check if the set is empty.
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        return \count($this->documents) === 0;
    }

    /**
     * Check if the set is not empty.
     *
     * @return boolean
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Get the number of documents.
     *
     * @return int<0,max>
     */
    public function count(): int
    {
        return \count($this->documents);
    }

    /**
     * Check if an offset exists.
     *
     * @param integer $offset The offset to check.
     * @return boolean
     */
    public function offsetExists($offset): bool // @phpcs:ignore
    {
        return isset($this->documents[$offset]);
    }

    /**
     * Get a document at the specified offset.
     *
     * @param integer $offset The offset to get.
     * @return Document|null
     */
    public function offsetGet($offset): ?Document // @phpcs:ignore
    {
        return $this->documents[$offset] ?? null;
    }

    /**
     * Set a document at the specified offset.
     *
     * @param integer|null $offset The offset to set.
     * @param Document     $value  The document to set.
     * @return void
     * @throws DocumentException When the value is not a Document instance.
     */
    public function offsetSet($offset, $value): void // @phpcs:ignore
    {
        if (!$value instanceof Document) {
            throw DocumentException::invalidType(Document::class);
        }

        if ($offset === null) {
            $this->documents[] = $value;
            return;
        }

        $this->documents[$offset] = $value;
    }

    /**
     * Unset a document at the specified offset.
     *
     * @param integer $offset The offset to unset.
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->documents[$offset]);

        $this->documents = \array_values($this->documents);
    }

    /**
     * Get the current document.
     *
     * @return Document|false
     */
    public function current(): Document|false
    {
        return \current($this->documents);
    }

    /**
     * Get the current key.
     *
     * @return integer|null
     */
    public function key(): ?int
    {
        return \key($this->documents);
    }

    /**
     * Move to the next document.
     *
     * @return void
     */
    public function next(): void
    {
        \next($this->documents);
    }

    /**
     * Rewind to the first document.
     *
     * @return void
     */
    public function rewind(): void
    {
        \reset($this->documents);
    }

    /**
     * Check if the current position is valid.
     *
     * @return boolean
     */
    public function valid(): bool
    {
        return \key($this->documents) !== null;
    }






    /**
     * Get the document's ids.
     *
     * @return integer[]
     */
    public function getDocumentIds(): array
    {
        return \array_map(fn(Document $document) => $document->documentId, $this->documents);
    }
}
