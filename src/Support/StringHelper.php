<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support;

/**
 * Helper methods for strings.
 */
class StringHelper
{
    /**
     * Normalise a table name.
     *
     * @param string $table The table name to treat.
     * @return string
     */
    public static function normaliseTableName(string $table): string
    {
        $table = \mb_strtolower($table);
        $table = \str_replace(['-', ' '], '_', $table);
        $table = (string) \preg_replace('/_+/', '_', $table);

        return $table;
    }

    /**
     * Build a table name by adding a suffix to a table name.
     *
     * Only normalises the suffix (not the table name, leave that the same as what the caller requested).
     *
     * @param string $table  The table name to treat.
     * @param string $suffix The suffix to add to the table name.
     * @return string
     */
    public static function addSuffixToTableName(string $table, string $suffix): string
    {
        $suffix = self::normaliseTableName($suffix);

        // if they both have an underscore at the point they join, join them with only one
        if (\str_ends_with($table, '_')) {
            if (\str_starts_with($suffix, '_')) {
                $suffix = \mb_substr($suffix, 1);
            }
        }

        return "{$table}{$suffix}";
    }

    /**
     * Truncate a string to a given length.
     *
     * @param string  $string The string to truncate.
     * @param integer $length The length to truncate to.
     * @return string
     */
    public static function truncate(string $string, int $length): string
    {
        return \mb_strlen($string) > $length
            ? \mb_substr($string, 0, $length) . '…'
            : $string;
    }

    /**
     * Convert a string to snake_case.
     *
     * @param string $parameterName The string to convert.
     * @return string
     */
    public static function toSnakeCase(string $parameterName): string
    {
        // keep letters, combining marks, and digits; others -> underscore
        $parameterName = \preg_replace('/[^\p{L}\p{M}\p{Nd}]+/u', '_', $parameterName) ?? '';

        // insert underscores at boundaries (treat \p{M}+ as part of the letter on the left)
        $patterns = [
            // non-uppercase letter(+marks) → UppercaseLetter (e.g., áCute → á_Cute, 用户ID → 用户_ID)
            '/((?:[\p{Ll}\p{Lo}\p{Lm}](?:\p{M}+)*))(\p{Lu})/u',
            // ACRONYM→Word (e.g., HTMLParser → HTML_Parser)
            '/(\p{Lu}+)(\p{Lu}\p{Ll})/u',
            // multiple letters → digit + uppercase letters (e.g., parse2JSON → parse_2_JSON, but not A1B2C3)
            '/((?:\p{L}(?:\p{M}+)*){2,})(\p{Nd}+\p{Lu}(?:\p{L}(?:\p{M}+)*)*)/u',
            // multiple letters → consecutive digits at end (e.g., test123 → test_123, ID42 → ID_42)
            '/((?:\p{L}(?:\p{M}+)*){2,})(\p{Nd}+)$/u',
            // letter → digit (e.g., test1 → test_1, a2 → a_2)
            '/(\p{L}(?:\p{M}+)*)(\p{Nd})/u',
            // digit → uppercase letter (e.g., 1B → 1_B, 2C → 2_C)
            '/(\p{Nd})(\p{Lu})/u',
        ];
        $parameterName = \preg_replace($patterns, '$1_$2', $parameterName) ?? '';

        // collapse/trim underscores
        $parameterName = \preg_replace('/_+/', '_', $parameterName) ?? '';
        $parameterName = \trim($parameterName, '_');

        // lowercase (UTF-8)
        $parameterName = \mb_strtolower($parameterName, 'UTF-8');

        return $parameterName;
    }
}
