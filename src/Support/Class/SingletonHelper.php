<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Class;

use DigitalAnomaly\AlteredLogic\Support\ValueStore;

/**
 * Helper class for managing singleton instances.
 *
 * @template T of object
 */
final class SingletonHelper
{
    /**
     * Get a singleton instance of a class.
     *
     * A unique key can be provided to differentiate between different singletons of the same class.
     *
     * @template TClass of object
     * @param class-string<TClass> $class         The class to get the instance of.
     * @param callable|null        $instantiation A callback that instantiates the class.
     * @param string               $uniqueKey     A key to differentiate between different singletons of the same class.
     * @return TClass
     */
    public static function instance( // @phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
        string $class,
        ?callable $instantiation = null,
        string $uniqueKey = '',
    ): object {

        // if no instantiation callback is provided, use a default one that simply instantiates the class
        if ($instantiation === null) {
            $instantiation = fn() => new $class();
        }

        $storageKey = $uniqueKey !== ''
            ? "multi-singleton.{$uniqueKey}.{$class}" // yes, not really a "singleton"
            : "singleton.{$class}";

        /** @var TClass $instance */
        $instance = ValueStore::get($storageKey, $instantiation);

        return $instance;
    }
}
