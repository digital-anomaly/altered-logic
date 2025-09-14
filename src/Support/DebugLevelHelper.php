<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support;

/**
 * Helper class for working with debug levels.
 */
final class DebugLevelHelper
{
    /**
     * Normalise the given debug level.
     *
     * @param integer|null $level The debug level to normalise.
     * @return integer|null The normalised debug level.
     */
    public static function normaliseLevel(?int $level): ?int
    {
        if ($level === null) {
            return null; // null means use the global debug level
        }

        return \max(0, \min($level, 2));
    }
}
