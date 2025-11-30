<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex;

use DigitalAnomaly\AlteredLogic\Interfaces\HasRegisteredNameInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\ModexModelInterface;

/**
 * Abstract class that represents an modex model.
 */
abstract class AbstractModexModel implements ModexModelInterface, HasRegisteredNameInterface
{
    use ModexModelTrait;
}
