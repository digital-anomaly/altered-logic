<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed;

use DigitalAnomaly\AlteredLogic\Embed\EmbedFaker;
use DigitalAnomaly\AlteredLogic\Embed\Internal\EmbedExecutor;
use DigitalAnomaly\AlteredLogic\Embed\Internal\EmbedGatedBatch;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\DTOs\EmbedGatedBatchIdentityDTO;
use DigitalAnomaly\AlteredLogic\Embed\Vector;
use DigitalAnomaly\AlteredLogic\Exceptions\EmbedException;
use DigitalAnomaly\AlteredLogic\Exceptions\RegistryException;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedCacheProfile;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedModelProfile;
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
    private ?string $modelProfileName = null;

    /** @var string|null The embed model to use directly (instead of using a model profile). */
    private ?string $modelName = null;

    /** @var string|null The embed cache profile to use. */
    private ?string $cacheProfileName = null;

    /** @var string|null The embed cache to use directly (instead of using a cache profile). */
    private ?string $cacheName = null;

    /** @var EmbedFaker|null The faker to use when generating embeddings. */
    private ?EmbedFaker $faker = null;



    /** @var integer|null The debug level to use: 0 = off, 1 = basic, 2 = verbose, null = use the default. */
    private ?int $debugLevel = null {
        set(?int $value) => DebugLevelHelper::normaliseLevel($value);
    }



    // /** @var string|null The user identifier to add to requests. */
    // public private(set) ?string $userIdentifier = null;



    /**
     * Specify the model profile to use when making requests.
     *
     * @param string|null $modelProfileName The embed model profile to use.
     * @return $this
     */
    public function modelProfile(?string $modelProfileName): static
    {
        $this->modelProfileName = $modelProfileName !== ''
            ? $modelProfileName
            : null;

        $this->modelName = null; // override the model

        return $this;
    }

    /**
     * Specify the model to use directly (instead of using a model profile).
     *
     * @param string|null $modelName The name of the model to use.
     * @return $this
     */
    public function model(?string $modelName): static
    {
        $this->modelProfileName = null; // override the model profile

        $this->modelName = $modelName !== ''
            ? $modelName
            : null;

        return $this;
    }



    /**
     * Set the embed cache profile to use.
     *
     * @param string|null $cacheProfileName The embed cache profile to use.
     * @return $this
     */
    public function cacheProfile(?string $cacheProfileName): static
    {
        $this->cacheProfileName = $cacheProfileName !== ''
            ? $cacheProfileName
            : null;

        $this->cacheName = null; // override the cache name

        return $this;
    }

    /**
     * Specify the cache to use directly (instead of using a cache profile).
     *
     * @param string|null $cacheName The name of the cache to use.
     * @return $this
     */
    public function cache(?string $cacheName): static
    {
        $this->cacheProfileName = null; // override the cache profile

        $this->cacheName = $cacheName !== ''
            ? $cacheName
            : null;

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
     * Flush embeddings - This processes all outstanding embeddings globally (across all models).
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

        $identity = new EmbedGatedBatchIdentityDTO(
            $this->resolveModelProfile(),
            $this->resolveCacheProfile(),
            $this->faker,
            $debugLevel,
        );

        return Registry::getEmbedGatedBatch($this->isDeferred, $identity);
    }

    /**
     * Resolve the embed model profile to use.
     *
     * @return EmbedModelProfile
     * @throws EmbedException If no profile or model could be resolved.
     */
    private function resolveModelProfile(): EmbedModelProfile
    {
        // 1. Check for explicit profile name
        if ($this->modelProfileName !== null) {
            try {
                return Registry::embedModelProfiles()->getOrThrow($this->modelProfileName);
            } catch (RegistryException $e) {
                throw EmbedException::embedModelProfileNotFound($this->modelProfileName, $e);
            }
        }

        // 2. Check for explicit model name
        if ($this->modelName !== null) {
            try {
                return Registry::embedModels()->getOrThrow($this->modelName)->getModelProfile();
            } catch (RegistryException $e) {
                throw EmbedException::embedModelNotFound($this->modelName, $e);
            }
        }

        // 3. Try the default profile
        $modelProfile = Registry::embedModelProfiles()->get(allowNotFound: true, allowEmpty: true);
        if ($modelProfile !== null) {
            return $modelProfile;
        }

        // 4. Try the default model
        $modelProfile = Registry::embedModels()->get(allowNotFound: true, allowEmpty: true)?->getModelProfile();
        if ($modelProfile !== null) {
            return $modelProfile;
        }

        // throw an exception if a default model profile has been specified but not found
        $defaultName = Registry::embedModelProfiles()::frameworkGetDefaultEntityName();
        if ($defaultName !== null) {
            throw EmbedException::embedModelProfileNotFound($defaultName);
        }

        // 5. Nothing found
        throw EmbedException::noEmbedModelOrProfileConfigured();
    }

    /**
     * Resolve the embed cache profile to use.
     *
     * @return EmbedCacheProfile|null
     * @throws EmbedException If cache/profile is specified but not found.
     */
    private function resolveCacheProfile(): ?EmbedCacheProfile
    {
        // 1. Check for explicit profile name
        if ($this->cacheProfileName !== null) {
            try {
                return Registry::embedCacheProfiles()->getOrThrow($this->cacheProfileName);
            } catch (RegistryException $e) {
                throw EmbedException::embedCacheProfileNotFound($this->cacheProfileName, $e);
            }
        }

        // 2. Check for explicit cache name
        if ($this->cacheName !== null) {
            try {
                return Registry::embedCaches()->getOrThrow($this->cacheName)->getCacheProfile();
            } catch (RegistryException $e) {
                throw EmbedException::embedCacheNotFound($this->cacheName, $e);
            }
        }

        // 3. Try the default profile
        $cacheProfile = Registry::embedCacheProfiles()->get(allowNotFound: true, allowEmpty: true);
        if ($cacheProfile !== null) {
            return $cacheProfile;
        }

        // 4. Try the default cache
        $cacheProfile = Registry::embedCaches()->get(allowNotFound: true, allowEmpty: true)?->getCacheProfile();
        if ($cacheProfile !== null) {
            return $cacheProfile;
        }

        // throw an exception if a default cache profile has been specified but not found
        $defaultName = Registry::embedCacheProfiles()::frameworkGetDefaultEntityName();
        if ($defaultName !== null) {
            throw EmbedException::embedCacheProfileNotFound($defaultName);
        }

        // 5. No caching configured (this is allowed)
        return null;
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
