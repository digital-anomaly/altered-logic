<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Config;

use DigitalAnomaly\AlteredLogic\Support\Class\SetPropertyMagicMethodsTrait;

/**
 * Manage general settings.
 *
 * @todo - incorporate this into Registry, so it's not its own singleton ?
 *
 * @method $this blockRequests(bool $blockRequests): self Set whether HTTP requests are blocked or not.
 *
 * AI instructions (magic-property-setter-methods): Keep the @method tags in this class up to date based on its properties.
 */
final class GeneralConfigStore
{
    use SetPropertyMagicMethodsTrait;



    /** @var boolean Whether HTTP requests are blocked or not. */
    public private(set) bool $blockRequests = false;
}
