<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed;

use DigitalAnomaly\AlteredLogic\Embed\EmbedFaker;
use DigitalAnomaly\AlteredLogic\Embed\Internal\EmbedExecutor;
use DigitalAnomaly\AlteredLogic\Embed\Internal\EmbedGatedBatch;
use DigitalAnomaly\AlteredLogic\Embed\Vector;
use DigitalAnomaly\AlteredLogic\Registry\Registry;
use DigitalAnomaly\AlteredLogic\Support\DebugLevelHelper;

/**
 * Builder for generating embeddings.
 */
abstract class AbstractEmbed
{
    /** @var boolean Whether the embeddings are being generated in a deferred manner or not. */
    protected bool $isDeferred = false;



    /** @var string|null The embed model profile to use. */
    private ?string $modelProfile = null;

    /** @var string|null The embed cache profile to use. */
    private ?string $cacheProfile = null;

    /** @var EmbedFaker|null The faker to use when generating embeddings. */
    private ?EmbedFaker $faker = null;



    /** @var integer|null The debug level to use: 0 = off, 1 = basic, 2 = verbose, null = use the default. */
    private ?int $debugLevel = null {
        set(?int $value) => DebugLevelHelper::normaliseLevel($value);
    }



    // /** @var string|null The user identifier to add to requests. */
    // public private(set) ?string $userIdentifier = null;



    /**
     * Set the embed model profile to use.
     *
     * @param string|null $modelProfile The embed model profile to use.
     * @return $this
     */
    public function modelProfile(string|null $modelProfile): static
    {
        $this->modelProfile = $modelProfile;

        return $this;
    }

    /**
     * Set the embed cache profile to use.
     *
     * @param string|null $cacheProfile The embed cache profile to use.
     * @return $this
     */
    public function cacheProfile(string|null $cacheProfile): static
    {
        $this->cacheProfile = $cacheProfile;

        return $this;
    }

    /**
     * Set the faker to use when generating embeddings.
     *
     * @param EmbedFaker|null $faker The faker to use when generating embeddings.
     * @return $this
     */
    public function faker(EmbedFaker|null $faker): static
    {
        $this->faker = $faker;

        return $this;
    }

    /**
     * Set the debug level to use: 0 = off, 1 = basic, 2 = verbose, null = use the default.
     *
     * @param integer|null $debugLevel The debug level to use: 0 = off, 1 = basic, 2 = verbose, null = use the default.
     * @return $this
     */
    public function debugLevel(int|null $debugLevel): static
    {
        $this->debugLevel = $debugLevel;

        return $this;
    }



    /**
     * Retrieve an embedding. If configured, cache/s will be checked first.
     *
     * @param mixed $source The item to embed - if not a string, it will be encoded as JSON.
     * @return Vector|null The embedding.
     */
    protected function _fetch(mixed $source): ?Vector
    {
        $results = $this->_fetchMany([$source]);

        return \array_key_exists(0, $results)
            ? $results[0]
            : null;
    }

    /**
     * Retrieve embeddings. If configured, cache will be checked first.
     *
     * A single request is sent to the AI provider if they support it.
     *
     * @param array<string|integer,mixed> $sources The items to embed - non-string items will be encoded as JSON.
     * @return array<integer,Vector|null> The embeddings, keyed by their position in the $sources array.
     */
    protected function _fetchMany(array $sources): array
    {
        $gatedBatch = $this->getCurrentGatedBatch();

        $gatedBatch->addItemsToResolve($sources);

        EmbedExecutor::processBatch($gatedBatch, !$this->isDeferred);

        $embeddings = $this->collectEmbeddings($gatedBatch);

        $gatedBatch->purgeItemsWithResolvedEmbedding();

        return $embeddings;
    }

    /**
     * Flush embeddings - Process all outstanding embeddings (across all models).
     *
     * @return void
     */
    protected function _flush(): void
    {
        foreach (Registry::getAllDeferredEmbedGatedBatches() as $gatedBatch) {

            EmbedExecutor::processBatch($gatedBatch, true);

            $gatedBatch->purgeItemsWithResolvedEmbedding();
        }
    }

    /**
     * Get the gated batch to use.
     *
     * @return EmbedGatedBatch
     */
    private function getCurrentGatedBatch(): EmbedGatedBatch
    {
        if ($this->debugLevel !== null) {
            $debugLevel = $this->debugLevel;
        } elseif (Registry::embedConfig()->debugLevel !== null) {
            $debugLevel = Registry::embedConfig()->debugLevel;
        } else {
            $debugLevel = 0;
        }

        return Registry::getEmbedGatedBatch(
            $this->isDeferred,
            Registry::embedModelProfiles()->getOrThrow((string) $this->modelProfile),
            Registry::embedCacheProfiles()->get((string) $this->cacheProfile, allowEmpty: true),
            $this->faker,
            $debugLevel,
        );
    }

    /**
     * Collect the resolved embeddings.
     *
     * @param EmbedGatedBatch $gatedBatch The gated batch to collect the resolved embeddings from.
     * @return array<integer,Vector|null> The resolved embeddings, keyed by their position in the $gatedBatch array.
     */
    private function collectEmbeddings(EmbedGatedBatch $gatedBatch): array
    {
        if ($this->isDeferred) {
            return [];
        }

        $embeddings = [];
        foreach ($gatedBatch as $item) {
            $embeddings[] = $item->getPendingEmbedding()->vector;
        }

        return $embeddings;
    }
}
