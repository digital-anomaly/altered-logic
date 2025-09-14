<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Class;

use Error;

/**
 * Trait that allows the caller to set properties using method calls.
 */
trait StaticEntryClassTrait
{
    /** @var string The class to "enter" (i.e. instantiate and call methods on). */
    // private const string CLASS_FQCN = '';

    /** @var boolean Whether the class is a singleton or not. */
    // private const bool IS_SINGLETON = false;



    /**
     * Private constructor.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }



    /**
     * Instantiate another class, call a method on it, and return the instance.
     *
     * e.g.
     * ```php
     * // this:
     * $results = Query::where('name', 'John')->where('age', 20)->get();
     *
     * // is equivalent to:
     * $results = new QueryBuilder()->where('name', 'John')->where('age', 20)->get();
     * ```
     *
     * i.e.
     * Normally QueryBuilder method where() can't be called statically AND non-statically.
     * Combining QueryBuilder with Query (that uses THIS trait) allows you to seemingly call where() both ways.
     *
     * @param string $method The called method.
     * @param array  $params The parameters passed.
     * @return mixed
     * @throws Error If the constant CLASS_FQCN or IS_SINGLETON is not set.
     */
    public static function __callStatic(string $method, array $params): mixed
    {
        $class = __CLASS__;
        $instance = null;

        // provide a few ways to obtain the instance

        // check if getInstance() method is defined
        if (\method_exists($class, 'getInstance')) {

            $instance = $class::getInstance();

        // } else {

        //     // check if CLASS_FQCN and IS_SINGLETON constants are defined
        //     if (\defined("$class::CLASS_FQCN") && \defined("$class::IS_SINGLETON")) {

        //         $enterableClass = static::CLASS_FQCN;
        //         $instance = static::IS_SINGLETON
        //             ? SingletonHelper::instance($enterableClass)
        //             : new $enterableClass();
        //     }
        }

        if ($instance === null) {
            throw new Error(
                // "Constant {$class}::CLASS_FQCN and IS_SINGLETON must be set, or getInstance() must be defined"
                "static method getInstance() must be defined"
            );
        }

        return \call_user_func_array([$instance, $method], $params);
    }
}
