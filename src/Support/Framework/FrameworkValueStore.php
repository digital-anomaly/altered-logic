<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Framework;

use Illuminate\Container\EntryNotFoundException;

/**
 * Register values with the current framework.
 */
final class FrameworkValueStore
{
    /** @var array<string,mixed> Static storage space, only used when a framework cannot be used. */
    private static array $values = [];



    /**
     * Store a value with the current framework, for the duration of the request lifecycle.
     *
     * @param string $key   The key to register the value with.
     * @param mixed  $value The value to register.
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        if (CapabilityDetector::laravelIsPresent()) {
            \app()->scoped($key, fn() => $value);
            return;
        }

        // todo - add other frameworks

        self::$values[$key] = $value;
    }

    /**
     * Retrieve a value from the current framework.
     *
     * @param string $key The key to retrieve the value for.
     * @return mixed
     */
    public static function get(string $key): mixed
    {
        if (CapabilityDetector::laravelIsPresent()) {

            try {
                return \app()->get($key);
            } catch (EntryNotFoundException $e) {
                return null;
            }
        }

        // todo - add other frameworks

        return self::$values[$key] ?? null;
    }

    /**
     * Forget a value.
     *
     * @param string $key The key to forget.
     * @return void
     */
    public static function forget(string $key): void
    {
        if (CapabilityDetector::laravelIsPresent()) {
            \app()->scoped($key, fn() => null); // can't really remove the value, so we'll just set it to null
            return;
        }

        // todo - add other frameworks

        unset(self::$values[$key]);
    }
}
