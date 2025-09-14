<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal;

use DigitalAnomaly\AlteredLogic\Modex\Internal\Traits\HasModexStructuredResponseTrait;

/**
 * Container for Modex's state.
 *
 * @todo - update properties to use PHP 8.4's get / set
 */
final class ModexCurrentState
{
    use HasModexStructuredResponseTrait;



    /** @var boolean Whether a response type override has been specified or not. */
    public bool $hasResponseTypeOverride = false;
}
