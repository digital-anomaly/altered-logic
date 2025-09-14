<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\ModexStateInterface;
use ErrorException;

/**
 * Key-value pairs of information, and some methods to help manage them.
 *
 * Used to collect Modex state information.
 */
class ModexState implements ModexStateInterface
{
    /** @var boolean Whether to allow arbitrary keys to be set or not. */
    protected bool $_allowArbitraryKeys = false;

    /** @var array<string,mixed> The state. */
    private array $_state = [];



    /**
     * Check whether arbitrary keys are allowed or not.
     *
     * @return boolean
     */
    private function _allowArbitraryKeys(): bool
    {
        if (\get_class($this) === self::class) {
            return true;
        }

        return $this->_allowArbitraryKeys;
    }



    /**
     * Retrieve a value.
     *
     * @param string $key     The key to get.
     * @param mixed  $default The default value to return if the key does not exist.
     * @return mixed The value of the key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (\property_exists($this, $key)) {
            return $this->{$key}; // @phpstan-ignore-line
        }

        if (!$this->_allowArbitraryKeys()) {
            throw new ErrorException("Undefined property: " . static::class . "::\${$key}");
        }

        return \array_key_exists($key, $this->_state)
            ? $this->_state[$key]
            : $default;
    }

    /**
     * Store a value.
     *
     * @param string $key   The key to set.
     * @param mixed  $value The value to set.
     * @return static
     */
    public function set(string $key, mixed $value): static
    {
        if (\property_exists($this, $key)) {
            $this->{$key} = $value; // @phpstan-ignore-line
        } elseif ($this->_allowArbitraryKeys()) {
            $this->_state[$key] = $value;
        } else {
            throw new ErrorException("Undefined property: " . static::class . "::\${$key}");
        }

        return $this;
    }



    /**
     * Get a value from the state.
     *
     * @param string $key The key to get.
     * @return mixed The value of the key.
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Set a value in the state.
     *
     * @param string $key   The key to set.
     * @param mixed  $value The value to set.
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }



    // /**
    //  * Set a value in the state by name. Allows for method chaining.
    //  *
    //  * @param string  $name      The name of the method.
    //  * @param mixed[] $arguments The arguments to pass to the method.
    //  * @return mixed The return value of the method.
    //  */
    // public function __call(string $name, array $arguments): mixed
    // {
    //     // if no arguments are passed, return the value
    //     if (\count($arguments) === 0) {
    //         return $this->get($name);
    //     }

    //     // if arguments are passed, set the value
    //     $this->set($name, $arguments[0]);

    //     return $this;
    // }



    /**
     * Increment a counter.
     *
     * @param string  $key    The key to increment.
     * @param integer $amount The amount to increment by.
     * @return static
     */
    public function inc(string $key, int $amount = 1): static
    {
        $count = $this->get($key, 0);
        if (!\is_int($count)) {
            $count = 0;
        }

        $this->set($key, $count + $amount);

        return $this;
    }

    /**
     * Decrement a counter.
     *
     * @param string  $key    The key to decrement.
     * @param integer $amount The amount to decrement by.
     * @return static
     */
    public function dec(string $key, int $amount = 1): static
    {
        return $this->inc($key, -$amount);
    }



    /**
     * Merge an array with an existing key.
     *
     * @param string       $key   The key to merge into.
     * @param array<mixed> $array The array to merge.
     * @return static
     */
    public function merge(string $key, array $array): static
    {
        $orig = $this->get($key, []);
        if (!\is_array($orig)) {
            $orig = [];
        }

        $this->set($key, \array_merge($orig, $array));

        return $this;
    }

    /**
     * Deep merge an array with an existing key.
     *
     * @param string       $key       The key to merge into.
     * @param array<mixed> ...$arrays The arrays to merge.
     * @return static
     */
    public function deepMerge(string $key, array ...$arrays): static
    {
        $orig = $this->get($key, []);
        if (!\is_array($orig)) {
            $orig = [];
        }

        $this->set($key, $this->_deepMerge($orig, ...$arrays));

        return $this;
    }

    /**
     * Perform an array deep merge.
     *
     * @param array<mixed> ...$arrays The arrays to merge.
     * @return array<mixed>
     */
    private function _deepMerge(array ...$arrays): array
    {
        $base = \array_shift($arrays) ?? [];

        foreach ($arrays as $append) {
            foreach ($append as $key => $value) {

                // don't try to merge values when their keys are integers
                if (\is_int($key)) {
                    $base[] = $value;
                    continue;
                }

                $base[$key] = \is_array($value) && isset($base[$key]) && \is_array($base[$key])
                    ? $this->_deepMerge($base[$key], $value)
                    : $value;
            }
        }

        return $base;
    }







    /**
     * Reset the state.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->_state = [];
    }







    /**
     * Check if a key exists.
     *
     * @param string $key The key to check.
     * @return boolean
     */
    public function has(string $key): bool
    {
        if (\property_exists($this, $key)) {
            return true;
        }

        if (\array_key_exists($key, $this->_state)) {
            return true;
        }

        return false;
    }

    /**
     * Check if a key is an instance of a certain type.
     *
     * @param string                                     $key  The key to check.
     * @param class-string|interface-string|trait-string $type The type to check.
     * @return boolean
     */
    public function instanceOf(string $key, string $type): bool
    {
        return $this->get($key) instanceof $type;
    }
}
