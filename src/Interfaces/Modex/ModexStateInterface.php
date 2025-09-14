<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Interfaces\Modex;

/**
 * Interface for a Modex state.
 */
interface ModexStateInterface
{
    /**
     * Retrieve a value.
     *
     * @param string $key     The key to get.
     * @param mixed  $default The default value to return if the key does not exist.
     * @return mixed The value of the key.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store a value.
     *
     * @param string $key   The key to set.
     * @param mixed  $value The value to set.
     * @return static
     */
    public function set(string $key, mixed $value): static;



    /**
     * Get a value from the state.
     *
     * @param string $key The key to get.
     * @return mixed The value of the key.
     */
    public function __get(string $key): mixed;

    /**
     * Set a value in the state.
     *
     * @param string $key   The key to set.
     * @param mixed  $value The value to set.
     * @return void
     */
    public function __set(string $key, mixed $value): void;



    // /**
    //  * Set a value in the state by name. Allows for method chaining.
    //  *
    //  * @param string  $name      The name of the method.
    //  * @param mixed[] $arguments The arguments to pass to the method.
    //  * @return mixed The return value of the method.
    //  */
    // public function __call(string $name, array $arguments): mixed;



    /**
     * Increment a counter.
     *
     * @param string  $key    The key to increment.
     * @param integer $amount The amount to increment by.
     * @return static
     */
    public function inc(string $key, int $amount = 1): static;

    /**
     * Decrement a counter.
     *
     * @param string  $key    The key to decrement.
     * @param integer $amount The amount to decrement by.
     * @return static
     */
    public function dec(string $key, int $amount = 1): static;



    /**
     * Merge an array with an existing key.
     *
     * @param string       $key   The key to merge into.
     * @param array<mixed> $array The array to merge.
     * @return static
     */
    public function merge(string $key, array $array): static;

    /**
     * Deep merge an array with an existing key.
     *
     * @param string       $key       The key to merge into.
     * @param array<mixed> ...$arrays The arrays to merge.
     * @return static
     */
    public function deepMerge(string $key, array ...$arrays): static;







    /**
     * Reset the state.
     *
     * @return void
     */
    public function reset(): void;







    /**
     * Check if a key exists.
     *
     * @param string $key The key to check.
     * @return boolean
     */
    public function has(string $key): bool;

    /**
     * Check if a key is an instance of a certain type.
     *
     * @param string                                     $key  The key to check.
     * @param class-string|interface-string|trait-string $type The type to check.
     * @return boolean
     */
    public function instanceOf(string $key, string $type): bool;
}
