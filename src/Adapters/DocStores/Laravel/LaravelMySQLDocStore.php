<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\DocStores\Laravel;

use DigitalAnomaly\AlteredLogic\Documents\Document;
use DigitalAnomaly\AlteredLogic\Exceptions\ResourceException;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocStoreInterface;
use DigitalAnomaly\AlteredLogic\Support\Laravel\LaravelQueryExceptionHelper;
use Illuminate\Database\Connection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * A MySQL document store that uses Laravel's database connection.
 */
final class LaravelMySQLDocStore implements DocStoreInterface
{
    /**
     * Constructor.
     *
     * @param string $databaseConnection The database connection to use, or a callback that returns it.
     * @param string $table              The table to use.
     */
    public function __construct(
        private string $databaseConnection,
        private string $table,
    ) {}



    /**
     * Get the database connection.
     *
     * @return Connection
     */
    private function getConnection(): Connection
    {
        return DB::connection($this->databaseConnection);
    }





    /**
     * Create the necessary resources / tables etc.
     *
     * @return void
     */
    public function initialise(): void
    {
        $this->getConnection()->statement(
            "CREATE TABLE IF NOT EXISTS `{$this->table}` (
                `document_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `category` VARCHAR(255) NOT NULL,
                `identifier` VARCHAR(255) NOT NULL,
                `metadata` JSON NOT NULL,
                UNIQUE INDEX `idx_unique_category_identifier` (`category`, `identifier`),
                INDEX `idx_identifier_category` (`identifier`, `category`)
            )"
        );
    }





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
    public function getDocument(string $category, string $identifier): ?Document
    {
        $callback = function () use ($category, $identifier) {

            $result = $this->getConnection()
                ->table($this->table)
                ->select('document_id', 'category', 'identifier', 'metadata')
                ->where('category', $category)
                ->where('identifier', $identifier)
                ->first();

            if ($result === null) {
                return null;
            }

            return new Document(
                (int) $result->document_id,
                (string) $result->category,
                (string) $result->identifier,
                \json_decode((string) $result->metadata, true) ?? [],
                null,
                null,
            );
        };

        $return = LaravelQueryExceptionHelper::runWrapped($callback);
        \assert($return instanceof Document || $return === null);

        return $return;
    }

    /**
     * Retrieve the metadata for documents based on their ids - just return the metadata json as a string.
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @param integer[] $documentIds The document's ids.
     * @return array<integer,string>
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function getDocumentMetadataJsonById(array $documentIds): array
    {
        $callback = function () use ($documentIds) {

            /** @var Collection<integer,stdClass> $rows */
            $rows = $this->getConnection()
                ->table($this->table)
                ->select('document_id', 'metadata')
                ->whereIn('document_id', $documentIds)
                ->get();

            return $rows->pluck('metadata', 'document_id')->map(fn($m) => (string) $m)->all();
        };

        /** @var array<integer,string> $return */
        $return = LaravelQueryExceptionHelper::runWrapped($callback);

        return $return;
    }



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
    public function addMeta(string $category, string $identifier, array $metadata): ?int
    {
        $callback = function () use ($category, $identifier, $metadata) {

            try {

                return $this->getConnection()
                    ->table($this->table)
                    ->insertGetId([
                        'category' => $category,
                        'identifier' => $identifier,
                        'metadata' => \json_encode($metadata),
                    ], 'document_id');

            } catch (UniqueConstraintViolationException $e) {

                $jsonParts = [];
                foreach ($metadata as $key => $value) {
                    $jsonParts[] = "'$.\"{$key}\"', " . \json_encode($value);
                }
                $jsonSetArgs = \implode(', ', $jsonParts);

                $this->getConnection()
                    ->update(
                        "UPDATE {$this->table}
                        SET metadata = JSON_SET(metadata, {$jsonSetArgs})
                        WHERE category = ? AND identifier = ?",
                        [$category, $identifier]
                    );

                return null;
            }
        };

        $return = LaravelQueryExceptionHelper::runWrapped($callback);
        \assert(\is_int($return) || $return === null);

        return $return;
    }



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
    public function removeMeta(string $category, string $identifier, array $keys): void
    {
        $callback = function () use ($category, $identifier, $keys) {

            $jsonParts = [];
            foreach ($keys as $key) {
                $jsonParts[] = "'$.\"{$key}\"'";
            }
            $jsonRemoveArgs = \implode(', ', $jsonParts);

            $this->getConnection()
                ->update(
                    "UPDATE {$this->table}
                    SET metadata = JSON_REMOVE(metadata, {$jsonRemoveArgs})
                    WHERE category = ? AND identifier = ?",
                    [$category, $identifier]
                );
        };

        LaravelQueryExceptionHelper::runWrapped($callback);
    }



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
    public function replaceMeta(string $category, string $identifier, array $metadata): void
    {
        // insert the new document if it doesn't exist, otherwise update it
        $callback = fn() => $this
            ->getConnection()
            ->table($this->table)
            ->upsert(
                [
                    [
                        'category' => $category,
                        'identifier' => $identifier,
                        'metadata' => \json_encode($metadata),
                    ]
                ],
                ['category', 'identifier'],
                ['metadata']
            );

        LaravelQueryExceptionHelper::runWrapped($callback);
    }



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
    public function removeDocument(string $category, string $identifier): void
    {
        $callback = fn() => $this
            ->getConnection()
            ->table($this->table)
            ->where('category', $category)
            ->where('identifier', $identifier)
            ->delete();

        LaravelQueryExceptionHelper::runWrapped($callback);
    }



    /**
     * Remove all documents from a category.
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @param string $category The category to purge.
     * @return void
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function purgeCategoryDocuments(string $category): void
    {
        $callback = fn() => $this
            ->getConnection()
            ->table($this->table)
            ->where('category', $category)
            ->delete();

        LaravelQueryExceptionHelper::runWrapped($callback);
    }



    /**
     * Remove ALL documents (regardless of category).
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @return void
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function purgeAllDocuments(): void
    {
        $callback = fn() => $this
            ->getConnection()
            ->table($this->table)
            ->delete();

        LaravelQueryExceptionHelper::runWrapped($callback);
    }
}
