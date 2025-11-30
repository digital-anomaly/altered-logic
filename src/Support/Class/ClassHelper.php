<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Class;

use ReflectionMethod;

/**
 * Helper class for managing classes.
 */
final class ClassHelper
{
    /**
     * Check if a class uses a specific trait (including parents and traits used by traits).
     *
     * @param class-string $class The class to check.
     * @param class-string $trait The trait to look for.
     * @return boolean
     */
    public static function classUsesTrait(string $class, string $trait): bool
    {
        $traits = self::getClassUsesRecursive($class);

        return \in_array($trait, $traits, true);
    }

    /**
     * Get the traits used by a class, including parents and traits used by traits.
     *
     * @param class-string $class The class to check.
     * @return list<class-string>
     */
    private static function getClassUsesRecursive(string $class): array
    {
        $parentClasses = \class_parents($class);
        if ($parentClasses === false) {
            $parentClasses = [];
        }
        $allClassses = \array_merge([$class => $class], $parentClasses);

        $traits = [];
        foreach ($allClassses as $tempClass) {
            $traits = \array_merge($traits, self::getTraitUsesRecursive($tempClass));
        }

        \sort($traits);

        return \array_values(\array_unique($traits));
    }

    /**
     * Get the traits used by a class, including traits used by traits.
     *
     * @param class-string $class The class to check.
     * @return list<class-string>
     */
    private static function getTraitUsesRecursive(string $class): array
    {
        $traits = \class_uses($class);
        if ($traits === false) {
            $traits = [];
        }

        foreach ($traits as $tempTrait) {
            $traits = \array_merge($traits, self::getTraitUsesRecursive($tempTrait));
        }

        return \array_values(\array_unique($traits));
    }



    /**
     * Check to see if the callable is actually a class that implements __invoke().
     *
     * @param mixed $class  The callable to check.
     * @param mixed $method The method to check.
     * @return boolean
     */
    public static function isClassWithNonStaticMethod(mixed $class, mixed $method): bool
    {
        if (!\is_string($class)) {
            return false;
        }

        if (!\is_string($method)) {
            return false;
        }

        if (!\class_exists($class)) {
            return false;
        }

        if (!\method_exists($class, $method)) {
            return false;
        }

        // only looking for non-static methods here
        $reflectionMethod = new ReflectionMethod($class, $method);
        if ($reflectionMethod->isStatic()) {
            return false;
        }

        return true;
    }
}
