<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Class;

use DigitalAnomaly\AlteredLogic\Support\Framework\DependencyInjection;
use ReflectionMethod;

/**
 * A class that wraps a "callable" class method, that's not actually a callable.
 *
 * When triggered, it instantiates the desired class and calls a method on that.
 */
final class CallableClassMethodWrapper
{
    /**
     * Constructor.
     *
     * @param class-string $class  The class to wrap.
     * @param string       $method The method to call.
     */
    public function __construct(
        private string $class,
        private string $method,
    ) {
    }



    /**
     * Call the callable.
     *
     * @param mixed ...$args The arguments to pass to the callable.
     * @return mixed
     */
    public function __invoke(mixed ...$args): mixed
    {
        $instance = DependencyInjection::instantiate($this->class);

        $callable = [$instance, $this->method];

        return \is_callable($callable)
            ? \call_user_func_array($callable, $args)
            : null;
    }

    /**
     * Give the caller access to the reflection method.
     *
     * This is used so the caller can access the method's signature.
     *
     * @return ReflectionMethod
     */
    public function getReflection(): ReflectionMethod
    {
        return new ReflectionMethod($this->class, $this->method);
    }
}
