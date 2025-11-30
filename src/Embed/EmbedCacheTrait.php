<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed;

use DigitalAnomaly\AlteredLogic\AlteredLogic;
use DigitalAnomaly\AlteredLogic\Exceptions\EmbedException;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedCacheProfile;
use DigitalAnomaly\AlteredLogic\Registry\HasRegisteredNameTrait;

/**
 * Trait for embed caches.
 */
trait EmbedCacheTrait
{
    use HasRegisteredNameTrait;



    /** @var EmbedCacheProfile|null The cache profile to use when only using this cache. */
    private ?EmbedCacheProfile $singleCacheProfile = null;



    /**
     * Build an embed cache profile containing just this cache.
     *
     * Will return the same object when called multiple times.
     *
     * @return EmbedCacheProfile
     * @throws EmbedException If the cache has not been registered.
     */
    public function getCacheProfile(): EmbedCacheProfile
    {
        if (!$this->isRegistered()) {
            throw EmbedException::embedCacheNotRegistered();
        }

        return $this->singleCacheProfile ??= new EmbedCacheProfile()->addCache($this->getRegisteredName());
    }

    /**
     * Register this embed cache.
     *
     * @param string  $name      The name of the cache to register.
     * @param boolean $isDefault Whether this is the default cache or not (the first one is default unless another is
     *                           specified).
     * @return void
     */
    public function register(string $name, bool $isDefault = false): void
    {
        AlteredLogic::registerEmbedCache($name, $this, $isDefault);
    }
}
