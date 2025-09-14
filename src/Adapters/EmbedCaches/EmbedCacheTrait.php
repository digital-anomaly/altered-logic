<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\EmbedCaches;

use DigitalAnomaly\AlteredLogic\AlteredLogic;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedCacheProfile;

/**
 * Trait for embed caches.
 */
trait EmbedCacheTrait
{
    /**
     * Register the embed cache.
     *
     * @param string  $name      The name of the cache to register.
     * @param boolean $isDefault Whether this is the default cache or not (the first one is default unless another is
     *                           specified).
     * @return void
     */
    public function register(string $name, bool $isDefault = false): void
    {
        // create an EmbedCacheProfile with one EmbedCache
        $cacheProfile = new EmbedCacheProfile()->addCache($this);

        AlteredLogic::registerEmbedCacheProfile($name, $cacheProfile, $isDefault);
    }
}
