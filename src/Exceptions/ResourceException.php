<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Exceptions;

use Throwable;

/**
 * Exceptions related to Resources (e.g. database tables).
 */
final class ResourceException extends AlteredLogicException
{
    /**
     * Thrown when a resource does not exist.
     *
     * @param Throwable $previous The previous exception.
     * @return self
     */
    public static function resourceDoesNotExist(Throwable $previous): self
    {
        return new self("The resource does not exist", previous: $previous);
    }

    /**
     * Thrown when a resource could not be initialised (i.e. the necessary resources / tables could not be created).
     *
     * @param Throwable $previous The previous exception.
     * @return self
     */
    public static function couldNotInitialiseResource(Throwable $previous): self
    {
        return new self("Could not initialise the resource", previous: $previous);
    }
}
