<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Profiles;

use DigitalAnomaly\AlteredLogic\AlteredLogic;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedCacheInterface;

/**
 * A profile containing a priority list of embed caches to use.
 */
final class EmbedCacheProfile
{
    /** @var EmbedCacheInterface[] The embed caches to use in order of preference. */
    private array $embedCaches = [];



    /**
     * Add an embed cache to the preference list.
     *
     * @param EmbedCacheInterface $cache The cache to add.
     * @return self
     */
    public function addCache(EmbedCacheInterface $cache): self
    {
        $this->embedCaches[] = $cache;

        return $this;
    }

    /**
     * Get the embed caches.
     *
     * @return EmbedCacheInterface[]
     */
    public function getCaches(): array
    {
        return $this->embedCaches;
    }



    /**
     * Register the embed cache.
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
