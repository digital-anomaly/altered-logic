<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Config;

use DigitalAnomaly\AlteredLogic\Support\Class\SetPropertyMagicMethodsTrait;
use DigitalAnomaly\AlteredLogic\Support\DebugLevelHelper;

/**
 * Manage modex settings.
 *
 * @todo - incorporate this into Registry, so it's not its own singleton ?s
 *
 * @method $this blockRequests(bool $blockRequests): self Set whether modex HTTP requests are blocked or not.
 * @method $this debugLevel(int $debugLevel): self Set the global debug level for modex use: 0 = off, 1 = basic, 2 = verbose.
 *
 * AI instructions (magic-property-setter-methods): Keep the @method tags in this class up to date based on its properties.
 */
final class ModexConfigStore
{
    use SetPropertyMagicMethodsTrait;



    /** @var boolean Whether modex HTTP requests are blocked or not. */
    public private(set) bool $blockRequests = false;

    /** @var integer|null The global debug level for modex use: 0 = off, 1 = basic, 2 = verbose. */
    public private(set) ?int $debugLevel = null { set(?int $value) => DebugLevelHelper::normaliseLevel($value); } // @phpcs:ignore
}
