<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\DocSearchers\Laravel;

use DigitalAnomaly\AlteredLogic\Credentials\CredentialsOverride;
use DigitalAnomaly\AlteredLogic\Documents\AbstractDocSearcher;
use DigitalAnomaly\AlteredLogic\Documents\DocResultSet;
use DigitalAnomaly\AlteredLogic\Documents\Document;
use DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatchItem;
use DigitalAnomaly\AlteredLogic\Embed\Embed;
use DigitalAnomaly\AlteredLogic\Exceptions\RegistryException;
use DigitalAnomaly\AlteredLogic\Exceptions\ResourceException;
use DigitalAnomaly\AlteredLogic\Registry\Registry;
use DigitalAnomaly\AlteredLogic\Support\Laravel\LaravelQueryExceptionHelper;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * A Postgres DocSearcher that uses Laravel's database connection.
 */
final class LaravelPostgresEmbedDocSearcher extends AbstractDocSearcher
{
    /**
     * Constructor.
     *
     * @param string $databaseConnection The database connection to use, or a callback that returns it.
     * @param string $table              The table to use.
     * @param string $embedModelProfile  The embed model profile to use.
     */
    public function __construct(
        private string $databaseConnection,
        private string $table,
        private string $embedModelProfile,
    ) {}



    /**
     * Get the embed model profile this doc-searcher uses (if any).
     *
     * @return string
     */
    public function getEmbedModelProfile(): string
    {
        return $this->embedModelProfile;
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
     * @todo review the DocSearcherInterface classes, (e.g. LaravelPostgresEmbedDocSearcher) and work out ways to configure these, e.g. choose the desired vector index type
     *
     * @return void
     * @throws RegistryException If the embed model profile is not found.
     */
    public function initialise(): void
    {
        $this->getConnection()->statement(
            "CREATE EXTENSION IF NOT EXISTS vector"
        );



        $dimensions = Registry::embedModelProfiles()->getOrThrow($this->embedModelProfile)->getDimensions();

        $this->getConnection()->statement(
            "CREATE TABLE IF NOT EXISTS \"{$this->table}\" (
                \"id\" BIGSERIAL PRIMARY KEY,
                \"document_id\" BIGINT NOT NULL,
                \"category\" VARCHAR(255),
                \"identifier\" VARCHAR(255),
                \"type\" VARCHAR(255),
                \"source\" TEXT,
                \"embedding\" VECTOR({$dimensions})
            )"
        );



        // regular indexes for direct lookups

        // for searches
        $this->getConnection()->statement(
            "CREATE INDEX IF NOT EXISTS \"{$this->table}_cat_type_idx\" "
                . "ON \"{$this->table}\" (category, type)",
        );

        // for deleting
        $this->getConnection()->statement(
            "CREATE INDEX IF NOT EXISTS \"{$this->table}_cat_id_type_idx\" "
                . "ON \"{$this->table}\" (category, identifier, type)",
        );

        // for deleting
        $this->getConnection()->statement(
            "CREATE INDEX IF NOT EXISTS \"{$this->table}_id_cat_type_idx\" "
                . "ON \"{$this->table}\" (identifier, category, type)",
        );



        // // composite indexes for filtered 4 searches

        // // for category-based searches
        // $this->getConnection()->statement(
        //     "CREATE INDEX IF NOT EXISTS \"{$this->table}_cat_embed_idx\" "
        //         . "ON \"{$this->table}\" (category) "
        //         . "INCLUDE (embedding)"
        // );

        // // for combined category and type searches
        // $this->getConnection()->statement(
        //     "CREATE INDEX IF NOT EXISTS \"{$this->table}_cat_type_embed_idx\" "
        //         . "ON \"{$this->table}\" (category, type) "
        //         . "INCLUDE (embedding)"
        // );



        // vector index for similarity search

        // // IVFFlat index - Good balance of speed and accuracy
        // $this->getConnection()->statement(
        //     "CREATE INDEX IF NOT EXISTS \"{$this->table}_embedding_idx\" "
        //         . "ON \"{$this->table}\" USING ivfflat (embedding vector_cosine_ops) "
        //         . "WITH (lists = 100)",
        // );

        // HNSW index - Faster but uses more memory
        $this->getConnection()->statement(
            "CREATE INDEX IF NOT EXISTS \"{$this->table}_hnsw_idx\" "
                . "ON \"{$this->table}\" "
                . "USING hnsw (embedding vector_cosine_ops)"
        );

        // // IVFSQ index - Memory efficient with good accuracy
        // $this->getConnection()->statement(
        //     "CREATE INDEX IF NOT EXISTS \"{$this->table}_ivfsq_idx\" "
        //         . "ON \"{$this->table}\" USING ivfsq (embedding vector_cosine_ops) "
        //         . "WITH (lists = 100, quantizer = 'sq')"
        // );

        // // IVFPQ index - Most memory efficient but lower accuracy
        // $this->getConnection()->statement(
        //     "CREATE INDEX IF NOT EXISTS \"{$this->table}_ivfpq_idx\" "
        //         . "ON \"{$this->table}\" USING ivfpq (embedding vector_cosine_ops) "
        //         . "WITH (lists = 100, quantizer = 'pq')"
        // );

        // // L2 distance alternatives (if needed)
        // $this->getConnection()->statement(
        //     "CREATE INDEX IF NOT EXISTS \"{$this->table}_embedding_l2_idx\" "
        //         . "ON \"{$this->table}\" USING ivfflat (embedding vector_l2_ops) "
        //         . "WITH (lists = 100)",
        // );

        // // L2 (Euclidean) distance index - Alternative to cosine similarity
        // $this->getConnection()->statement(
        //     "CREATE INDEX IF NOT EXISTS \"{$this->table}_hnsw_l2_idx\" "
        //         . "ON \"{$this->table}\" "
        //         . "USING hnsw (embedding vector_l2_ops)"
        // );
    }





    /**
     * Store resolved searchables.
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @param DocSearchableGatedBatchItem[] $items The items to save.
     * @return void
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function storeSearchables(array $items): void
    {
        $rows = $this->generateRows($items);
        if ($rows === []) {
            return;
        }

        $callback = fn() => $this->getConnection()->table($this->table)->insert($rows);

        LaravelQueryExceptionHelper::runWrapped($callback);
    }

    /**
     * Generate the rows of data to save.
     *
     * @param DocSearchableGatedBatchItem[] $items The items to save.
     * @return array<integer,array<string,mixed>>
     */
    private function generateRows(array $items): array
    {
        $rows = [];
        foreach ($items as $item) {

            $source = $item->getPendingEmbedding()->source;
            $vector = $item->getPendingEmbedding()->vector;

            \assert($vector !== null);

            $docSearchable = $item->getDocSearchable();

            $rows[] = [
                'document_id' => $docSearchable->documentId,
                'category' => $docSearchable->category,
                'identifier' => $docSearchable->identifier,
                'type' => $docSearchable->type,
                'source' => $source,
                'embedding' => \json_encode($vector->coordinates()),
            ];
        }

        return $rows;
    }





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
    public function removeDocumentSearchables(string $category, string $identifier, ?string $type = null): void
    {
        $callback = fn() => $this
            ->getConnection()
            ->table($this->table)
            ->where('category', $category)
            ->where('identifier', $identifier)
            ->when(
                $type !== null,
                fn($query) => $query->where('type', $type),
            )->delete();

        LaravelQueryExceptionHelper::runWrapped($callback);
    }





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
    public function removeSearchablesFromCategory(string $category, ?string $type = null): void
    {
        $callback = fn() => $this
            ->getConnection()
            ->table($this->table)
            ->where('category', $category)
            ->when(
                $type !== null,
                fn($query) => $query->where('type', $type),
            )->delete();

        LaravelQueryExceptionHelper::runWrapped($callback);
    }





    /**
     * Remove ALL searchable records (regardless of category).
     *
     * Must throw a ResourceException if the necessary resources / tables don't exist (i.e. haven't been initialised).
     *
     * @return void
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function removeAllSearchables(): void
    {
        $callback = fn() => $this
            ->getConnection()
            ->table($this->table)
            ->delete();

        LaravelQueryExceptionHelper::runWrapped($callback);
    }





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
     * @param string[]                 $categories          The categories to search in.
     * @param string[]                 $types               The types of searchables to search against (searches all
     *                                                      types by default).
     * @param string                   $source              The search input.
     * @param integer                  $limit               The maximum number of results to return.
     * @param integer|null             $debugLevel          The debug level to use: 0 = off, 1 = basic, 2 = verbose,
     *                                                      null = use the default.
     * @param CredentialsOverride|null $credentialsOverride The credentials to use instead of each model's own.
     * @return DocResultSet
     * @throws ResourceException If the necessary resources / tables don't exist.
     */
    public function search(
        array $categories,
        array $types,
        string $source,
        int $limit = 20,
        ?int $debugLevel = null,
        ?CredentialsOverride $credentialsOverride = null,
    ): DocResultSet {

        $callback = function () use ($categories, $types, $source, $limit, $debugLevel, $credentialsOverride) {

            $vector = Embed::new()->modelProfile($this->embedModelProfile)
                ->credentials($credentialsOverride)
                ->debugLevel($debugLevel)
                ->fetch($source);

            if ($vector === null) {
                return new DocResultSet([]);
            }

            $vectorStr = \json_encode($vector->coordinates());



            $subQuery = $this->getConnection()
                ->table($this->table)
                ->select(
                    'document_id',
                    'category',
                    'identifier',
                    'type',
                    'source'
                )
                ->selectRaw(
                    'embedding <=> ? AS cosine_distance, ROW_NUMBER() OVER (PARTITION BY document_id ORDER BY embedding <=> ? ASC) as row_num',
                    [$vectorStr, $vectorStr]
                )
                ->whereIn('category', $categories)
                ->when(
                    $types !== [],
                    fn($q) => $q->whereIn('type', $types)
                );

            /** @var array<integer,stdClass> $rows */
            $rows = $this->getConnection()
                ->query()
                ->fromSub($subQuery, 'ranked_docs')
                ->where('row_num', 1)
                ->orderBy('cosine_distance', 'asc')
                ->limit(\max(1, $limit))
                ->get()
                ->all();

            $documents = [];
            foreach ($rows as $row) {

                $documents[] = new Document(
                    (int) $row->document_id,
                    (string) $row->category,
                    (string) $row->identifier,
                    [],
                    (string) $row->source,
                    (string) $row->type,
                );
            }

            return new DocResultSet($documents);
        };

        $return = LaravelQueryExceptionHelper::runWrapped($callback);

        \assert($return instanceof DocResultSet);

        return $return;
    }
}
