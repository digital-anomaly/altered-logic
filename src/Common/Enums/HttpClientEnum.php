<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Common\Enums;

/**
 * Represents the available HTTP clients.
 */
enum HttpClientEnum: string
{
    case Laravel = 'laravel';
    // case Symfony = 'symfony';
    case Curl = 'curl';
}
