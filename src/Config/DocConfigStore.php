<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Config;

use DigitalAnomaly\AlteredLogic\Support\Class\SetPropertyMagicMethodsTrait;
use DigitalAnomaly\AlteredLogic\Support\DebugLevelHelper;

/**
 * Manage document settings.
 *
 * @todo - incorporate this into Registry, so it's not its own singleton ?
 *
 * @method $this debugLevel(int $debugLevel): self Set the global debug level for document use: 0 = off, 1 = basic, 2 = verbose.
 * @method $this deferBatchSize(int $deferBatchSize): self Set the number of items to include in a batch.
 *
 * AI instructions (magic-property-setter-methods): Keep the @method tags in this class up to date based on its properties.
 */
final class DocConfigStore
{
    use SetPropertyMagicMethodsTrait;



    /** @var integer|null The global debug level for document use: 0 = off, 1 = basic, 2 = verbose. */
    public private(set) ?int $debugLevel = null { set(?int $value) => DebugLevelHelper::normaliseLevel($value); } // @phpcs:ignore

    /** @var integer The number of items to include in a batch. */
    public private(set) int $deferBatchSize = 20 { set(int $value) => \max(1, $value); } // @phpcs:ignore
}
