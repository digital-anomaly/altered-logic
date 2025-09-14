<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Exceptions;

use Throwable;

/**
 * Exceptions related to Dependency Injection.
 */
final class DependencyInjectonException extends AlteredLogicException
{
    /**
     * Thrown when the class does not exist.
     *
     * @param class-string $class The class that does not exist.
     * @return self
     */
    public static function classDoesNotExist(string $class): self
    {
        return new self("The class {$class} does not exist");
    }

    /**
     * Thrown when the class could not be instantiated.
     *
     * @param class-string   $class    The class that could not be instantiated.
     * @param Throwable|null $previous The previous throwable used for the exception chaining.
     * @return self
     */
    public static function couldNotInstantiateClass(string $class, ?Throwable $previous = null): self
    {
        return new self("The class {$class} could not be instantiated", previous: $previous);
    }

    /**
     * Thrown when the given callable is invalid.
     *
     * @return self
     */
    public static function invalidCallable(): self
    {
        return new self('The given callable is invalid');
    }

    /**
     * Thrown when a callable's parameters cannot be resolved.
     *
     * @param Throwable|null $previous The previous throwable used for the exception chaining.
     * @return self
     */
    public static function parametersCannotBeResolved(?Throwable $previous = null): self
    {
        return new self('The parameters cannot be resolved', previous: $previous);
    }
}
