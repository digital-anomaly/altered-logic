<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\EmbedCaches\Laravel;

use DigitalAnomaly\AlteredLogic\Embed\AbstractEmbedCache;
use DigitalAnomaly\AlteredLogic\Embed\Vector;
use DigitalAnomaly\AlteredLogic\Exceptions\ResourceException;
use DigitalAnomaly\AlteredLogic\Support\Laravel\LaravelDatabaseHelper;
use DigitalAnomaly\AlteredLogic\Support\Laravel\LaravelQueryExceptionHelper;
use DigitalAnomaly\AlteredLogic\Support\StringHelper;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * A MySQL EmbedCache that uses Laravel's database connection.
 */
final class LaravelMySQLEmbedCache extends AbstractEmbedCache
{
    /**
     * Constructor.
     *
     * @param string $databaseConnection The database connection to use, or a callback that returns it.
     * @param string $tablePrefix        The table prefix to use.
     */
    public function __construct(
        private string $databaseConnection,
        private string $tablePrefix,
    ) {
    }



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
     * @param string  $tableSuffix The table suffix to use.
     * @param integer $dimensions  The number of dimensions the embeddings have.
     * @return void
     */
    public function initialise(string $tableSuffix, int $dimensions): void
    {
        $table = StringHelper::addSuffixToTableName($this->tablePrefix, $tableSuffix);

        $this->getConnection()->statement(
            "CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `source` MEDIUMTEXT NOT NULL,
                `embedding` MEDIUMTEXT NOT NULL,
                UNIQUE KEY `{$table}_source_idx` (`source`(768))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }





    /**
     * Get multiple embeddings from the cache.
     *
     * Assume that there is at least one source in the $sources array.
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @param string   $tableSuffix The table suffix to use.
     * @param string[] $sources     The source text contents to retrieve embeddings for.
     * @return array<string,Vector|null> The found embeddings, keyed by their source text.
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function getEmbeddings(string $tableSuffix, array $sources): array
    {
        $table = StringHelper::addSuffixToTableName($this->tablePrefix, $tableSuffix);

        $callback = fn() => $this->getConnection()
            ->table($table)
            ->whereIn('source', $sources)
            ->get();

        /** @var Collection<integer,stdClass> $rows */
        $rows = LaravelQueryExceptionHelper::runWrapped($callback);

        // start with nulls in order to match the sources array
        $embeddings = \array_combine(
            $sources,
            \array_fill(0, \count($sources), null),
        );

        foreach ($rows as $row) {
            /** @var string $source */
            $source = $row->source;
            $embeddings[$source] = LaravelDatabaseHelper::makeVectorFromDbRow($row);
        }

        return $embeddings;
    }

    /**
     * Store multiple embeddings in the cache.
     *
     * Assume that there is at least one embedding in the $embeddings array.
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @param string               $tableSuffix The table suffix to use.
     * @param array<string,Vector> $embeddings  The embeddings to store, keyed by their source text.
     * @return void
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function storeEmbeddings(string $tableSuffix, array $embeddings): void
    {
        $table = StringHelper::addSuffixToTableName($this->tablePrefix, $tableSuffix);

        $records = [];
        foreach ($embeddings as $source => $embedding) {
            $records[] = [
                'source' => $source,
                'embedding' => \json_encode($embedding->coordinates())
            ];
        }

        $callback = fn() => $this->getConnection()
            ->table($table)
            ->upsert(
                $records,
                ['source'],  // unique identifier
                ['embedding'], // columns to update
            );

        LaravelQueryExceptionHelper::runWrapped($callback);
    }
}
