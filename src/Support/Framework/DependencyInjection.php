<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Framework;

use DigitalAnomaly\AlteredLogic\Common\Enums\FrameworksEnum;
use DigitalAnomaly\AlteredLogic\Exceptions\DependencyInjectonException;
use DigitalAnomaly\AlteredLogic\Support\Class\CallableClassMethodWrapper;
use DigitalAnomaly\Schema\Support\ReflectionHelper;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use Throwable;

/**
 * A class for dependency injection using the relevant framework.
 */
final class DependencyInjection
{
    /**
     * Instantiate a class.
     *
     * @param class-string        $class        The class to instantiate.
     * @param array<string,mixed> $paramsByType The parameters to pass by type (if present).
     * @param array<string,mixed> $paramsByName The parameters to pass by name (if present).
     * @return object
     * @throws DependencyInjectonException When the class could not be instantiated.
     */
    public static function instantiate(string $class, array $paramsByType = [], array $paramsByName = []): object
    {
        if (!\class_exists($class)) {
            throw DependencyInjectonException::classDoesNotExist($class);
        }

        // use the framework's replacement for the class, if it has one
        // todo: add tests for this pathway:
        //       - where the framework has a replacement for the class, to make sure that is used instead
        if (self::frameworkHasReplacementForClass($class)) {
            $instance = self::useFrameworkReplacementForClass($class);
            if (\is_object($instance)) {
                return $instance;
            }
        }



        // no constructor - just instantiate the class
        $reflectionMethod = new ReflectionClass($class)->getConstructor();
        if ($reflectionMethod === null) {
            return new $class();
        }

        // resolve the constructor parameters and instantiate the class
        try {
            $params = self::resolveParameters($reflectionMethod, $paramsByType, $paramsByName);
        } catch (Throwable $e) {
            throw DependencyInjectonException::couldNotInstantiateClass($class, $e);
        }

        return (new ReflectionClass($class))->newInstanceArgs($params);
    }

    /**
     * Call a callable, with the given parameters.
     *
     * @param callable            $callable     The callable to call.
     * @param array<string,mixed> $paramsByType The parameters to pass by type (if present).
     * @param array<string,mixed> $paramsByName The parameters to pass by name (if present).
     * @return mixed
     * @throws DependencyInjectonException When the callable cannot be called with the given parameters.
     */
    public static function call(callable $callable, array $paramsByType = [], array $paramsByName = []): mixed
    {
        $reflectionMethod = $callable instanceof CallableClassMethodWrapper
            ? $callable->getReflection()
            : ReflectionHelper::buildReflectionFromCallable($callable);

        if ($reflectionMethod === null) {
            throw DependencyInjectonException::invalidCallable();
        }

        // resolve the parameters and call the callable
        try {
            $params = self::resolveParameters($reflectionMethod, $paramsByType, $paramsByName);
        } catch (Throwable $e) {
            throw $e instanceof DependencyInjectonException
                ? $e // already an instance of DependencyInjectonException, so don't wrap it in another
                : DependencyInjectonException::parametersCannotBeResolved($e);
        }

        return \call_user_func_array($callable, $params);
    }



    /**
     * Check if the framework has a replacement registered for the class.
     *
     * @param string $class The class to check.
     * @return boolean
     */
    private static function frameworkHasReplacementForClass(string $class): bool
    {
        $framework = CapabilityDetector::pickFunctionalityToUse([
            FrameworksEnum::Laravel,
            FrameworksEnum::NoFramework,
        ]);

        if ($framework === FrameworksEnum::Laravel) {
            return \app()->has($class);
        }

        // todo - add other frameworks

        return false;
    }

    /**
     * Use the framework's replacement for the class.
     *
     * @param string $class The class to use the replacement for.
     * @return mixed
     */
    private static function useFrameworkReplacementForClass(string $class): mixed
    {
        $framework = CapabilityDetector::pickFunctionalityToUse([
            FrameworksEnum::Laravel,
            FrameworksEnum::NoFramework,
        ]);

        if ($framework === FrameworksEnum::Laravel) {
            return \app()->make($class);
        }

        // todo - add other frameworks

        return null;
    }



    /**
     * Derive the parameters for a callable using Laravel.
     *
     * @param ReflectionFunctionAbstract $reflectionMethod The reflection method to derive the parameters for.
     * @param array<string,mixed>        $paramsByType     The parameters to pass by type (if present).
     * @param array<string,mixed>        $paramsByName     The parameters to pass by name (if present).
     * @return array<string,mixed>
     * @throws DependencyInjectonException When a parameter cannot be resolved.
     */
    private static function resolveParameters(
        ReflectionFunctionAbstract $reflectionMethod,
        array $paramsByType = [],
        array $paramsByName = [],
    ): array {

        // todo - add other frameworks
        $framework = CapabilityDetector::pickFunctionalityToUse([
            FrameworksEnum::Laravel,
            FrameworksEnum::NoFramework,
        ]);



        $params = [];
        foreach ($reflectionMethod->getParameters() as $parameter) {



            // pick by paramater name
            $name = $parameter->getName();

            if (isset($paramsByName[$name])) {
                $params[$name] = $paramsByName[$name];
                continue;
            }



            // pick by parameter type
            $reflectionType = $parameter->getType();

            // don't support union or intersection types
            if (!$reflectionType instanceof ReflectionNamedType) {
                throw DependencyInjectonException::parametersCannotBeResolved();
            }

            $type = $reflectionType->getName();
            if (isset($paramsByType[$type])) {
                $params[$name] = $paramsByType[$type];
                continue;
            }



            // use the default value when available for PHP types
            if ($reflectionType->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $params[$name] = $parameter->getDefaultValue();
                    continue;
                }

                throw DependencyInjectonException::parametersCannotBeResolved();
            }

            // pick by parameter type - using Laravel
            if ($framework === FrameworksEnum::Laravel) {
                $params[$name] = \app()->make($type);
                continue;
            }

            // todo - add other frameworks



            throw DependencyInjectonException::parametersCannotBeResolved();
        }

        return $params;
    }
}
