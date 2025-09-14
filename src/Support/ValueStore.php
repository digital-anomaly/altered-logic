<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support;

use DigitalAnomaly\AlteredLogic\Support\Framework\FrameworkValueStore;

/**
 * Register values with the current framework.
 */
final class ValueStore
{
    /** @var array<string,mixed> The values stored in the value-store. */
    private array $values = [];

    /** @var string The framework key to use for the value-store. */
    private const string STORAGE_KEY = __CLASS__ . '.value-store';



    /**
     * Private constructor.
     *
     * @return void
     */
    private function __construct()
    {}



    /**
     * Get the singleton instance.
     *
     * @return self
     */
    private static function instance(): self
    {
        $instance = FrameworkValueStore::get(self::STORAGE_KEY);

        if (!$instance instanceof self) {

            $instance = new self();
            FrameworkValueStore::set(self::STORAGE_KEY, $instance);
        }

        return $instance;
    }



    // /**
    //  * Set a value in the framework value-store.
    //  *
    //  * @param string $storageKey The key to store the value under.
    //  * @param mixed  $value      The value to set.
    //  * @return void
    //  */
    // public static function set(string $storageKey, mixed $value): void
    // {
    //     self::instance()->values[$storageKey] = $value;
    // }

    /**
     * Get a value from the framework value-store.
     *
     * @param string        $storageKey      The key to retrieve the value from.
     * @param callable|null $defaultCallback Callable to call to build the default value. Will be stored for next time.
     * @return mixed
     */
    public static function get(string $storageKey, ?callable $defaultCallback = null): mixed
    {
        $instance = self::instance();

        if (\array_key_exists($storageKey, $instance->values)) {
            return $instance->values[$storageKey];
        }

        $return = \is_callable($defaultCallback)
            ? $defaultCallback()
            : null;

        if ($return !== null) {
            $instance->values[$storageKey] = $return;
        }

        return $return;
    }



    /**
     * Remove the value-store from the framework.
     *
     * @return void
     */
    public static function cleanUp(): void
    {
        FrameworkValueStore::forget(self::STORAGE_KEY);
    }
}
