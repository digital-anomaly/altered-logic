<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed;

use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedModelInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\HasRegisteredNameInterface;

/**
 * Abstract class that represents an embedding model.
 */
abstract class AbstractEmbedModel implements EmbedModelInterface, HasRegisteredNameInterface
{
    use EmbedModelTrait;
}
