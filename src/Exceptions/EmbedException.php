<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Exceptions;

use Throwable;

/**
 * Exceptions related to embeddings.
 */
class EmbedException extends AlteredLogicException
{
    // /**
    //  * Thrown when the embed cache profile could not be resolved.
    //  *
    //  * @param string|null $name The name of the cache profile that could not be resolved.
    //  * @return self
    //  */
    // public static function cannotResolveCacheProfile(?string $name): self
    // {
    //     return $name !== null
    //         ? new self("The embed cache profile \"{$name}\" could not be resolved")
    //         : new self('The embed default cache profile could not be resolved');
    // }

    // /**
    //  * Thrown when the embed model profile could not be resolved.
    //  *
    //  * @param string|null $name The name of the model profile that could not be resolved.
    //  * @return self
    //  */
    // public static function cannotResolveModelProfile(?string $name): self
    // {
    //     return $name !== null
    //         ? new self("The embed model profile \"{$name}\" could not be resolved")
    //         : new self('The embed default model profile could not be resolved');
    // }

    /**
     * Thrown when the embed API client could not be resolved.
     *
     * @param Throwable|null $previous The previous exception.
     * @return self
     */
    public static function embedApiClientCouldNotBeResolved(?Throwable $previous = null): self
    {
        return new self(
            "The embedding API client could not be resolved. Have the AI provider's details been registered?",
            previous: $previous,
        );
    }

    /**
     * Thrown when an invalid embed API client class is provided.
     *
     * @param string $className The invalid class name.
     * @return self
     */
    public static function invalidEmbedApiClient(string $className): self
    {
        return new self("Invalid embed API client class: {$className}");
    }





    /**
     * Thrown when the dimensions for a given model are unknown.
     *
     * @param string $model The model that was not found.
     * @return self
     */
    public static function dimensionsUnknown(string $model): self
    {
        return new self(
            "The dimensions for the model \"{$model}\" are unknown (this can happen if you're using a custom model). "
            . "Please specify the dimensions",
        );
    }





    /**
     * Thrown when HTTP requests are blocked and a faker didn't provide a vector.
     *
     * @param string|null $model The model that was not found.
     * @return self
     */
    public static function httpRequestsAreBlocked(?string $model): self
    {
        return new self(
            $model !== null
                ? "Requests are blocked, and no faker was configured for model \"{$model}\""
                : "Requests are blocked, and no faker was configured",
        );
    }
}
