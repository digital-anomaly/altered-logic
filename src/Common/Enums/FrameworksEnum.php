<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Common\Enums;

/**
 * Represents the available frameworks.
 */
enum FrameworksEnum: string
{
    case Laravel = 'laravel';
    // case Symfony = 'symfony';
    case NoFramework = 'no-framework';
}
