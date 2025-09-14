<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Laravel;

use DigitalAnomaly\AlteredLogic\Embed\Vector;

/**
 * Helper for Laravel database related things.
 */
class LaravelDatabaseHelper
{
    /**
     * Make a Vector from a set of coordinates, from a query row.
     *
     * @param object|null $row The row to make a Vector from.
     * @return Vector|null The Vector, or null if the coordinates are null.
     */
    public static function makeVectorFromDbRow(?object $row): ?Vector
    {
        if ($row === null) {
            return null;
        }

        if (!\property_exists($row, 'embedding')) {
            return null;
        }

        if (!\is_string($row->embedding)) {
            return null;
        }

        $coordinates = \json_decode($row->embedding, true);
        if (!\is_array($coordinates)) {
            return null;
        }

        /** @var array<integer,float> $coordinates */
        return new Vector($coordinates);
    }
}
