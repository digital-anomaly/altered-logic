<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Config;

use DigitalAnomaly\AlteredLogic\Embed\EmbedFaker;
use DigitalAnomaly\AlteredLogic\Support\Class\SetPropertyMagicMethodsTrait;
use DigitalAnomaly\AlteredLogic\Support\DebugLevelHelper;

/**
 * Manage embedding settings.
 *
 * @todo - incorporate this into Registry, so it's not its own singleton ?
 *
 * @method $this deferBatchSize(int $deferBatchSize): self Set the number of items to include in a batch.
 * @method $this blockRequests(bool $blockRequests): self Set whether embedding HTTP requests are blocked or not.
 * @method $this faker(EmbedFaker|null $faker): self Set the faker to use when generating embeddings.
 * @method $this debugLevel(int $debugLevel): self Set the global debug level for embed use: 0 = off, 1 = basic, 2 = verbose.
 *
 * AI instructions (magic-property-setter-methods): Keep the @method tags in this class up to date based on its properties.
 */
final class EmbedConfigStore
{
    use SetPropertyMagicMethodsTrait;



    /** @var int<1,max> The number of items to include in a batch. */
    public private(set) int $deferBatchSize = 20 { set(int $value) => \max(1, $value); } // @phpcs:ignore



    /** @var boolean Whether embedding HTTP requests are blocked or not. */
    public private(set) bool $blockRequests = false;

    /** @var EmbedFaker|null The faker to use when generating embeddings. */
    public private(set) ?EmbedFaker $faker = null;

    /** @var integer|null The global debug level for embed use: 0 = off, 1 = basic, 2 = verbose. */
    public private(set) ?int $debugLevel = null { set(?int $value) => DebugLevelHelper::normaliseLevel($value); } // @phpcs:ignore
}
