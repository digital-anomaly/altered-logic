<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Profiles;

use DigitalAnomaly\AlteredLogic\AlteredLogic;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedCacheInterface;
use DigitalAnomaly\AlteredLogic\Registry\Registry;

/**
 * A profile containing a priority list of embed caches to use.
 */
final class EmbedCacheProfile
{
    /** @var string[] The cache names to use in order of preference. */
    private array $cachePreferences = [];



    /**
     * Add a cache to the preference list.
     *
     * @param string $registeredCacheName The name of the cache to add.
     * @return self
     */
    public function addCache(string $registeredCacheName): self
    {
        $this->cachePreferences[] = $registeredCacheName;

        return $this;
    }

    /**
     * Add multiple caches to the preference list.
     *
     * @param string[] $registeredCacheNames The names of the caches to add.
     * @return self
     */
    public function addCaches(array $registeredCacheNames): self
    {
        foreach ($registeredCacheNames as $registeredCacheName) {
            $this->addCache($registeredCacheName);
        }

        return $this;
    }



    /**
     * Get the embed caches.
     *
     * @return EmbedCacheInterface[]
     */
    public function getCaches(): array
    {
        $caches = [];
        foreach ($this->cachePreferences as $cacheName) {
            $cache = Registry::embedCaches()->get($cacheName, allowNotFound: true);
            if ($cache !== null) {
                $caches[] = $cache;
            }
        }

        return $caches;
    }



    /**
     * Register this embed cache profile.
     *
     * @param string  $name      The name of the profile to register.
     * @param boolean $isDefault Whether this is the default profile or not (the first one is default unless another is
     *                           specified).
     * @return void
     */
    public function register(string $name, bool $isDefault = false): void
    {
        AlteredLogic::registerEmbedCacheProfile($name, $this, $isDefault);
    }
}
