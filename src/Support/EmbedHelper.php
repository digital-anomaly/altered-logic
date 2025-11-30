<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support;

/**
 * Helper class for embeddings.
 */
final class EmbedHelper
{
    /**
     * Normalise a set of sources (turn non-strings to strings).
     *
     * @param array<string|integer,mixed> $sources The items to embed - non-string items will be encoded as JSON.
     * @return string[] The normalised input.
     */
    public static function normaliseSources(array $sources): array
    {
        $normalised = [];
        foreach (\array_values($sources) as $source) {
            $normalised[] = self::normaliseSource($source);
        }

        return $normalised;
    }

    /**
     * Normalise source data (turn non-strings to strings).
     *
     * Later, other types like objects representing images can be allowed to pass.
     *
     * @param mixed $source The source to normalise.
     * @return string
     */
    public static function normaliseSource(mixed $source): string
    {
        if (\is_string($source)) {

            // todo - normalise string sources (remove whitespace, etc) - create a normaliser class to do this?, that gets registered with the EmbedModelProfile

            // $source = \strtolower($source);
            // $source = (string) \preg_replace('/\s+/', ' ', $source);
            // return \trim($source);

            return $source;
        }

        // turn non-string sources into JSON
        $source = \json_encode($source);

        if (\is_string($source)) {
            return $source;
        }

        return '';
    }
}
