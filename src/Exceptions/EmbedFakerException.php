<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Exceptions;

use DigitalAnomaly\AlteredLogic\Support\StringHelper;

/**
 * Exceptions related to faker embeddings.
 */
class EmbedFakerException extends EmbedException
{
    /**
     * Thrown when the next embedding is not specified when in "in-order" mode.
     *
     * @param string $requestedKey The key that was not found.
     * @return self
     */
    public static function nextEmbeddingNotSpecified(string $requestedKey): self
    {
        $requestedKey = StringHelper::truncate($requestedKey, 20);

        return new self(
            "The fake embedding for \"{$requestedKey}\" was requested, but no more are left",
        );
    }

    /**
     * Thrown when an unexpected fake embedding is requested when in "in-order" mode.
     *
     * @param string $expectedKey  The expected embedding key.
     * @param string $requestedKey The actual embedding key that was requested.
     * @return self
     */
    public static function unexpectedFakeEmbeddingRequested(string $expectedKey, string $requestedKey): self
    {
        $expectedKey = StringHelper::truncate($expectedKey, 20);
        $requestedKey = StringHelper::truncate($requestedKey, 20);

        return new self(
            "The next expected fake embedding is for key \"{$expectedKey}\", but \"{$requestedKey}\" was requested",
        );
    }

    /**
     * Thrown when a vector is not found for a given key.
     *
     * @param string $requestedKey The key that was not found.
     * @return self
     */
    public static function fakeEmbeddingNotFound(string $requestedKey): self
    {
        $requestedKey = StringHelper::truncate($requestedKey, 20);

        return new self(
            "The fake embedding for \"{$requestedKey}\" was not found. "
            . "Please specify an embedding for this using \$faker->embedding('{$requestedKey}', \$vector), "
            . "enable random embeddings using `->random()`, "
            . "or fall-back to real embeddings using `->dontThrow()`",
        );
    }

    /**
     * Thrown when a fake embedding has the wrong dimensions.
     *
     * @param string  $key                The key that was not found.
     * @param integer $expectedDimensions The number of dimensions the vector should have.
     * @param integer $actualDimensions   The number of dimensions the vector has.
     * @return self
     */
    public static function fakeEmbedDimensionsMismatch(
        string $key,
        int $expectedDimensions,
        int $actualDimensions
    ): self {

        $key = StringHelper::truncate($key, 20);

        return new self(
            "The fake embedding for key \"{$key}\" has the {$actualDimensions} dimensions, "
            . "but {$expectedDimensions} were expected",
        );
    }
}
