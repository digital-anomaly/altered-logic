<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Exceptions;

use Throwable;

/**
 * Exceptions related to credentials.
 */
class CredentialsException extends AlteredLogicException
{
    /**
     * Thrown when an invalid credentials override is provided.
     *
     * @param string $reason The reason the override is invalid.
     * @return self
     */
    public static function invalidCredentialsOverride(string $reason): self
    {
        return new self("Invalid credentials override: {$reason}");
    }

    /**
     * Thrown when credentials specified via ->credentials() could not be found in the registry.
     *
     * @param string         $name        The name of the credentials that could not be found.
     * @param string         $modelName   The model being built when the override was matched.
     * @param string         $provider    The provider the model belongs to.
     * @param boolean        $isUniversal Whether the override applies to all providers, or came from a provider map.
     * @param Throwable|null $previous    The previous exception.
     * @return self
     */
    public static function overrideCredentialsNotFound(
        string $name,
        string $modelName,
        string $provider,
        bool $isUniversal,
        ?Throwable $previous = null,
    ): self {

        $source = $isUniversal
            ? 'applied to all providers'
            : 'matched from the provider map';

        return new self(
            "The credentials \"$name\" were not found in the registry. They were specified via ->credentials() "
            . "($source), and were matched while building model \"$modelName\" (provider \"$provider\")",
            previous: $previous,
        );
    }
}
