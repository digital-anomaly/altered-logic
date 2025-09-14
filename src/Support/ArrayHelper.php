<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support;

/**
 * Helper methods for arrays.
 */
final class ArrayHelper
{
    /**
     * Remove empty elements from an array.
     *
     * @param array<string,mixed>        $array       The array to prune.
     * @param array<string|integer>|null $keys        The keys to check (if null, all keys will be checked).
     * @param array<mixed>               $emptyValues The values considered to be empty.
     * @return array<string,mixed>
     */
    public static function removeEmptyElements(
        array $array,
        ?array $keys = null,
        array $emptyValues = ['', null, []],
    ): array {

        foreach ($array as $key => $value) {

            if ($keys !== null && !\in_array($key, $keys, true)) {
                continue;
            }

            if (\in_array($value, $emptyValues, true)) {
                unset($array[$key]);
            }
        }

        return $array;
    }
}
