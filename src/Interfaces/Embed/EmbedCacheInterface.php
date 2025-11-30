<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Interfaces\Embed;

use DigitalAnomaly\AlteredLogic\Embed\Vector;
use DigitalAnomaly\AlteredLogic\Exceptions\ResourceException;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedCacheProfile;

/**
 * Interface for caching embedding vectors.
 */
interface EmbedCacheInterface
{
    /**
     * Create the necessary resources / tables etc.
     *
     * @param string  $tableSuffix The table suffix to use.
     * @param integer $dimensions  The number of dimensions the embeddings have.
     * @return void
     */
    public function initialise(string $tableSuffix, int $dimensions): void;

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
    public function getEmbeddings(string $tableSuffix, array $sources): array;

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
    public function storeEmbeddings(string $tableSuffix, array $embeddings): void;



    /**
     * Build an embed cache profile containing just this cache.
     *
     * Will return the same object when called multiple times.
     *
     * @return EmbedCacheProfile
     */
    public function getCacheProfile(): EmbedCacheProfile;



    /**
     * Register this embed cache.
     *
     * @param string  $name      The name of the cache to register.
     * @param boolean $isDefault Whether this is the default cache or not (the first one is default unless another is
     *                           specified).
     * @return void
     */
    public function register(string $name, bool $isDefault = false): void;
}
