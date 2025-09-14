<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Class;

use ArgumentCountError;
use Error;

/**
 * Trait that allows the caller to set properties using method calls.
 */
trait SetPropertyMagicMethodsTrait
{
    /**
     * Set properties using magic method calls.
     *
     * e.g.
     * ```php
     * // this:
     * $person = new Person()->firstName('John')->lastName('Doe');
     *
     * //is equivalent to:
     * $person = new Person();
     * $person->firstName = 'John';
     * $person->lastName = 'Doe';
     * ```
     *
     * @param string $method The called method.
     * @param array  $params The parameters passed.
     * @return mixed
     * @throws Error If the method is not defined.
     * @throws ArgumentCountError If the method is called with too few arguments.
     */
    public function __call(string $method, array $params): mixed
    {
        if (!\property_exists($this, $method)) {

            $class = __CLASS__;
            throw new Error("Call to undefined method {$class}::{$method}()");
        }

        // check the first parameter was passed
        if (!\array_key_exists(0, $params)) {

            $class = __CLASS__;
            $count = \count($params);
            throw new ArgumentCountError(
                "Too few arguments to function {$class}::{$method}(), {$count} passed and exactly 1 expected",
            );
        }

        $this->{$method} = $params[0];

        return $this;
    }
}
