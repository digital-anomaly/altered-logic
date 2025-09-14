<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\EmbedCaches\Laravel;

use DigitalAnomaly\AlteredLogic\Adapters\EmbedCaches\EmbedCacheTrait;
use DigitalAnomaly\AlteredLogic\Embed\Vector;
use DigitalAnomaly\AlteredLogic\Exceptions\ResourceException;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedCacheInterface;
use DigitalAnomaly\AlteredLogic\Support\Laravel\LaravelDatabaseHelper;
use DigitalAnomaly\AlteredLogic\Support\Laravel\LaravelQueryExceptionHelper;
use DigitalAnomaly\AlteredLogic\Support\StringHelper;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * A Postgres EmbedCache that uses Laravel's database connection.
 */
final class LaravelPostgresEmbedCache implements EmbedCacheInterface
{
    use EmbedCacheTrait;



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
        $this->getConnection()->statement(
            "CREATE EXTENSION IF NOT EXISTS vector"
        );



        $table = StringHelper::addSuffixToTableName($this->tablePrefix, $tableSuffix);

        $this->getConnection()->statement(
            "CREATE TABLE IF NOT EXISTS \"{$table}\" (
                \"id\" BIGSERIAL PRIMARY KEY,
                \"source\" TEXT NOT NULL,
                \"embedding\" VECTOR({$dimensions}),
                CONSTRAINT \"{$table}_source_idx\" UNIQUE (\"source\")
            )"
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

        try {

            $rows = $this->getConnection()
                ->table($table)
                ->whereIn('source', $sources)
                ->get();

        } catch (QueryException $e) {
            throw LaravelQueryExceptionHelper::wrapResourceException($e);
        }

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

        try {

            $this->getConnection()
                ->table($table)
                ->upsert(
                    $records,
                    ['source'], // unique identifier
                    ['embedding'] // columns to update
                );

        } catch (QueryException $e) {
            // todo use LaravelQueryExceptionHelper::runWrapped() here instead
            throw LaravelQueryExceptionHelper::wrapResourceException($e);
        }
    }
}
