<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal;

use DigitalAnomaly\Schema\Schema;
use DigitalAnomaly\Schema\SchemaWalk;
use DigitalAnomaly\Schema\Types\ClassType;
use ReflectionClass;

/**
 * Loops through a Schema and updates the descriptions to mention which classes are native PHP classes.
 */
final class SchemaModifier
{
    /**
     * Loop through a Schema and updates the descriptions to mention which classes are native PHP classes.
     *
     * @param Schema|null $schema The schema to update.
     * @return void
     */
    public static function addNativePHPClassComments(?Schema $schema): void
    {
        $callback = function (Schema $schema) {

            if (!$schema->type instanceof ClassType) {
                return;
            }

            $classType = $schema->type;
            $fqcn = $classType->fqcn;

            // don't label stdClass as a native PHP class, it's a bit pointless to do so
            if ($fqcn === 'stdClass') {
                return;
            }

            if (!self::isNativeClass($fqcn)) {
                return;
            }

            $schema->description = $schema->description !== ''
                ? $schema->description . " (PHP class $fqcn)"
                : "PHP class $fqcn";
        };

        SchemaWalk::walk($schema, $callback);
    }

    /**
     * Check if a class FQCN is a native PHP class.
     *
     * @param string|null $fqcn The fully qualified class name.
     * @return boolean
     */
    private static function isNativeClass(?string $fqcn): bool
    {
        if ($fqcn === null) {
            return false;
        }

        if (!\class_exists($fqcn, false)) {
            return false;
        }

        $reflectionClass = new ReflectionClass($fqcn);

        return $reflectionClass->isInternal();
    }
}
