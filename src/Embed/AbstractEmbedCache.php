<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed;

use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedCacheInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\HasRegisteredNameInterface;

/**
 * Abstract class that represents an embedding cache.
 */
abstract class AbstractEmbedCache implements EmbedCacheInterface, HasRegisteredNameInterface
{
    use EmbedCacheTrait;
}
