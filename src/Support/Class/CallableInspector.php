<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Class;

use DigitalAnomaly\AlteredLogic\Exceptions\ModexException;
use DigitalAnomaly\Schema\Types\CallableType;
use ReflectionMethod;

/**
 * A class to help with identifying callables.
 *
 * This class is used to help with working with callables, and to identify if a callable is a valid non-callable.
 *
 * e.g. callables are valid, but so is "Namespace\Class::method" or ["Namespace\Class", "method"]
 *      (which this package will instantiate before calling the method)
 */
final class CallableInspector
{
    /** @var boolean Whether the "callable" is valid. (Updated during instantiation). */
    public private(set) bool $isValid = false;

    /** @var boolean Whether the callable is actually callable. */
    public private(set) bool $isCallable = false;



    /** @var callable|array{0:string|object,1:string}|null The callable itself. */
    public private(set) mixed $callable = null;

    /** @var class-string|null The class of the callable (if relevant). */
    public private(set) string|null $classFqcn = null;

    /** @var string|null The class of the callable (if relevant). */
    public private(set) string|null $className = null;

    /** @var string|null The method of the callable (if relevant). */
    public private(set) string|null $method = null;

    /** @var boolean Whether the callable refers to a class. */
    public private(set) bool $refersToClass = false;

    /** @var boolean Whether the callable refers to an object. */
    public private(set) bool $refersToObject = false;

    /** @var ReflectionMethod[] The reflection methods built from the callable (if relevant). */
    private array $reflectionMethods = [];



    /** @var boolean Failure flag - the callable refers to an object's constructor (it's already been instantiated). */
    public private(set) bool $refersToObjectConstructor = false;

    /** @var boolean Failure flag - the object method does not exist. */
    public private(set) bool $objectMethodDoesNotExist = false;

    /** @var boolean Failure flag - the object method is not callable. */
    public private(set) bool $objectMethodIsNotCallable = false;



    /** @var boolean Failure flag - the class does not exist. */
    public private(set) bool $classDoesNotExist = false;

    /** @var boolean Failure flag - the class constructor is not callable (protected/private) - can't instantiate. */
    public private(set) bool $classConstructorIsNotCallable = false;

    /** @var boolean Failure flag - the class method does not exist. */
    public private(set) bool $classMethodDoesNotExist = false;

    /** @var boolean Failure flag - the class method is not callable (protected/private). */
    public private(set) bool $classMethodIsNotCallable = false;





    /**
     * Constructor.
     *
     * @param mixed $callable The callable to identify.
     */
    public function __construct(mixed $callable)
    {
        $this->initialise($callable);
    }



    /**
     * Create a new CallableInspector instance for a definer.
     *
     * @param mixed $definer The definer to identify.
     * @return self
     * @throws ModexException If the definer is invalid.
     */
    public static function newDefiner(mixed $definer): self
    {
        $i = new self($definer);
        if ($i->isValid) {
            return $i;
        }

        // give a useful exception
        $class = (string) $i->classFqcn;
        $method = (string) $i->method;
        throw match (true) {
            $i->refersToObjectConstructor => ModexException::cannotUseObjectConstructorAsDefiner($class),
            $i->objectMethodDoesNotExist => ModexException::cannotUseMissingObjectMethodAsDefiner($class, $method),
            $i->objectMethodIsNotCallable => ModexException::cannotUseNonCallableObjectMethodAsDefiner($class, $method), // @phpcs:ignore
            $i->classDoesNotExist => ModexException::cannotUseMissingClassAsDefiner($class),
            $i->classConstructorIsNotCallable => ModexException::cannotUseClassWithNonCallableConstructorAsDefiner($class), // @phpcs:ignore
            $i->classMethodDoesNotExist => ModexException::cannotUseMissingClassMethodAsDefiner($class, $method),
            $i->classMethodIsNotCallable => ModexException::cannotUseNonCallableClassMethodAsDefiner($class, $method),
            default => ModexException::invalidDefiner(), // general, invalid definer
        };
    }

    /**
     * Create a new CallableInspector instance for a loop orchestrator.
     *
     * @param mixed $loopOrchestrator The loop orchestrator to identify.
     * @return self
     * @throws ModexException If the loop orchestrator is invalid.
     */
    public static function newloopOrchestrator(mixed $loopOrchestrator): self
    {
        $i = new self($loopOrchestrator);
        if ($i->isValid) {
            return $i;
        }

        // give a useful exception
        $class = (string) $i->classFqcn;
        $method = (string) $i->method;
        throw match (true) {
            $i->refersToObjectConstructor => ModexException::cannotUseObjectConstructorAsLoopOrchestrator($class),
            $i->objectMethodDoesNotExist => ModexException::cannotUseMissingObjectMethodAsLoopOrchestrator($class, $method), // @phpcs:ignore
            $i->objectMethodIsNotCallable => ModexException::cannotUseNonCallableObjectMethodAsLoopOrchestrator($class, $method), // @phpcs:ignore
            $i->classDoesNotExist => ModexException::cannotUseMissingClassAsLoopOrchestrator($class),
            $i->classConstructorIsNotCallable => ModexException::cannotUseClassWithNonCallableConstructorAsLoopOrchestrator($class), // @phpcs:ignore
            $i->classMethodDoesNotExist => ModexException::cannotUseMissingClassMethodAsLoopOrchestrator($class, $method),
            $i->classMethodIsNotCallable => ModexException::cannotUseNonCallableClassMethodAsLoopOrchestrator($class, $method), // @phpcs:ignore
            default => ModexException::invalidLoopOrchestrator(), // general, invalid orchestrator
        };
    }

    /**
     * Create a new CallableInspector instance for a tool.
     *
     * @param mixed $tool The tool to identify.
     * @return self
     * @throws ModexException If the tool is invalid.
     */
    public static function newTool(mixed $tool): self
    {
        $i = new self($tool);
        if ($i->isValid && !$i->refersToConstructor()) { // constructors are valid callables, but not for tools
            return $i;
        }

        // give a useful exception
        $class = (string) $i->classFqcn;
        $method = (string) $i->method;
        throw match (true) {

            // check for special situations that are invalid for tool use:
            // constructors can't be used as tools, as they don't return anything
            $i->refersToConstructor() => ModexException::cannotUseConstructorAsTool($class),

            // check for normal situations that are invalid
            // $i->refersToObjectConstructor => ModexException::cannotUseObjectConstructorAsTool($class),
            $i->objectMethodDoesNotExist => ModexException::cannotUseMissingObjectMethodAsTool($class, $method),
            $i->objectMethodIsNotCallable => ModexException::cannotUseNonCallableObjectMethodAsTool($class, $method),
            $i->classDoesNotExist => ModexException::cannotUseMissingClassAsTool($class),
            $i->classConstructorIsNotCallable => ModexException::cannotUseClassWithNonCallableConstructorAsTool($class), // @phpcs:ignore
            $i->classMethodDoesNotExist => ModexException::cannotUseMissingClassMethodAsTool($class, $method),
            $i->classMethodIsNotCallable => ModexException::cannotUseNonCallableClassMethodAsTool($class, $method),
            default => ModexException::invalidTool(), // general, invalid tool
        };
    }

    /**
     * Create a new CallableInspector instance for a structured response.
     *
     * @param mixed $structuredResponse The structured response to identify.
     * @return self
     * @throws ModexException If the structured response is invalid.
     */
    public static function newStructuredResponse(mixed $structuredResponse): self
    {
        $i = new self($structuredResponse);
        if ($i->isValid) {
            return $i;
        }

        // give a useful exception
        $class = (string) $i->classFqcn;
        $method = (string) $i->method;
        throw match (true) {
            $i->refersToObjectConstructor => ModexException::cannotUseObjectConstructorAsStructuredResponse($class),
            $i->objectMethodDoesNotExist => ModexException::cannotUseMissingObjectMethodAsStructuredResponse($class, $method), // @phpcs:ignore
            $i->objectMethodIsNotCallable => ModexException::cannotUseNonCallableObjectMethodAsStructuredResponse($class, $method), // @phpcs:ignore
            $i->classDoesNotExist => ModexException::cannotUseMissingClassAsStructuredResponse($class),
            $i->classConstructorIsNotCallable => ModexException::cannotUseClassWithNonCallableConstructorAsStructuredResponse($class), // @phpcs:ignore
            $i->classMethodDoesNotExist => ModexException::cannotUseMissingClassMethodAsStructuredResponse($class, $method), // @phpcs:ignore
            $i->classMethodIsNotCallable => ModexException::cannotUseNonCallableClassMethodAsStructuredResponse($class, $method), // @phpcs:ignore
            default => ModexException::invalidStructuredResponse(), // general, invalid structured response
        };
    }







    /**
     * Initialise based on the given callable.
     *
     * @param mixed $callable The callable to identify.
     * @return void
     */
    private function initialise(mixed $callable): void
    {
        if ($this->breakIntoArrayParts($callable)) {

            /** @var array{0:string|object,1:string} $parts */
            $parts = $this->callable;
            $this->reviewArrayParts($parts);

            return;
        }

        // other sorts of callables, e.g. closures, method strings, etc.
        if (\is_callable($callable)) {
            $this->isValid = true;
            $this->callable = $callable;
            $this->isCallable = true;
        }
    }

    /**
     * Take a "callable" and turn it into an array of [classOrObject, method], if possible.
     *
     * @param mixed $callable The callable to identify.
     * @return boolean
     */
    private function breakIntoArrayParts(mixed $callable): bool
    {
        // special cases to take into account…

        // test the object to see if it has an __invoke() method below
        if (\is_object($callable)) {
            $callable = [$callable, '__invoke'];
        }

        // turn a string like "Namespace\Class::method" into an array so it's handled below
        if (\is_string($callable) && \mb_strpos($callable, '::') !== false) {
            $callable = \explode('::', $callable, 2);
        }

        // take a class and direct it towards its __invoke() method
        if (\is_string($callable) && \class_exists($callable)) {
            if (\method_exists($callable, '__invoke')) {
                $callable = [$callable, '__invoke'];
            } else {
                $callable = [$callable, '__construct'];
            }
        }



        // the callable must be an array with two values
        if (!\is_array($callable)) {
            return false;
        }
        if (\count($callable) !== 2) {
            return false;
        }

        // the first value must be a string or object, and the method must be a string
        $classOrObject = \array_values($callable)[0];
        if (!\is_string($classOrObject) && !\is_object($classOrObject)) {
            return false;
        }
        $method = \array_values($callable)[1];
        if (!\is_string($method)) {
            return false;
        }

        // the FORMAT is valid, the callable MIGHT be valid…
        $this->callable = [$classOrObject, $method];

        return true;
    }

    /**
     * Review the basic parts of the callable.
     *
     * @param array{0:string|object,1:string} $parts The parts of the callable.
     * @return void
     */
    private function reviewArrayParts(array $parts): void
    {
        $objectOrClass = $parts[0];
        $method = $parts[1];

        $isClass = \is_string($objectOrClass);
        if ($isClass) {
            /** @var class-string $objectOrClass */
            $classFqcn = $objectOrClass;
        } else {
            $classFqcn = \get_class($objectOrClass);
        }

        $this->isValid = true;
        $this->isCallable = \is_callable($this->callable);
        $this->classFqcn = $classFqcn;
        $this->className = self::extractClassFromFqcn($classFqcn);
        $this->method = $method;
        $this->refersToClass = $isClass;
        $this->refersToObject = !$isClass;

        // for classes…
        if ($isClass) {

            // make sure the class exists
            if (!\class_exists($classFqcn)) {
                $this->classDoesNotExist = true;
                $this->isValid = false;
                return;
            }

            // check that the constructor is callable, or doesn't exist
            if ((\method_exists($classFqcn, '__construct')) && (!$this->classMethodIsCallable('__construct'))) {
                $this->classConstructorIsNotCallable = true;
                $this->isValid = false;
                return;
            }

            if ($method !== '__construct') {

                // the method must exist
                if (!\method_exists($classFqcn, $method)) {
                    $this->classMethodDoesNotExist = true;
                    $this->isValid = false;
                    return;
                }

                // the method must be callable
                if (!$this->classMethodIsCallable($method)) {
                    $this->classMethodIsNotCallable = true;
                    $this->isValid = false;
                    return;
                }
            }

        // for objects…
        } else {

            // it shouldn't refer to the constructor (as it's already been instantiated)
            if ($method === '__construct') {
                $this->refersToObjectConstructor = true;
                $this->isValid = false;
                return;
            }

            // check that the method exists
            if (!\method_exists($objectOrClass, $method)) {
                $this->objectMethodDoesNotExist = true;
                $this->isValid = false;
                return;
            }

            // make sure the method is callable
            if (!\is_callable($parts)) {
                $this->objectMethodIsNotCallable = true;
                $this->isValid = false;
                return;
            }
        }
    }

    /**
     * Check if the class constructor is callable.
     *
     * @param string $method The method to check.
     * @return boolean
     */
    private function classMethodIsCallable(string $method): bool
    {
        $reflectionMethod = $this->buildReflectionMethod($method);

        if (!$reflectionMethod->isPublic()) {
            return false;
        }

        // if ($reflectionMethod->isStatic()) {
        //     return false;
        // }

        return true;
    }

    /**
     * Build a reflection method for the callable.
     *
     * @param string $method The method to build a reflection method for.
     * @return ReflectionMethod
     */
    private function buildReflectionMethod(string $method): ReflectionMethod
    {
        /** @var class-string $classFqcn */
        $classFqcn = $this->classFqcn;
        return $this->reflectionMethods[$method] ??= new ReflectionMethod($classFqcn, $method);
    }

    /**
     * Extract the class from a FQCN.
     *
     * @param class-string $classFqcn The FQCN to get the class from.
     * @return string
     */
    private static function extractClassFromFqcn(string $classFqcn): string
    {
        $temp = \explode('\\', $classFqcn);

        return \end($temp);
    }





    /**
     * Check if the callable is valid.
     *
     * @return boolean
     */
    public function isValid(): bool
    {
        return $this->callable !== null;
    }

    /**
     * Check if the callable is actually callable.
     *
     * @return boolean
     */
    public function isCallable(): bool
    {
        return $this->isCallable;
    }

    /**
     * Check if the callable refers to a class or object's constructor.
     *
     * @return boolean
     */
    public function refersToConstructor(): bool
    {
        return $this->method === '__construct';
    }

    /**
     * Check if the callable refers to a class constructor.
     *
     * @return boolean
     */
    public function refersToClassConstructor(): bool
    {
        return $this->refersToClass && $this->refersToConstructor();
    }

    /**
     * Check if the callable refers to an object's constructor (which is invalid).
     *
     * @return boolean
     */
    public function refersToObjectConstructor(): bool
    {
        return $this->refersToObject && $this->refersToConstructor();
    }





    /**
     * Build a CallableType instance from the callable.
     *
     * @param string|null $name        The name of the type.
     * @param string|null $description The description of the type.
     * @return CallableType
     * @throws ModexException If the callable is invalid.
     */
    public function buildCallableType(?string $name = null, ?string $description = null): CallableType
    {
        if (!$this->isValid()) {
            throw ModexException::invalidCallable();
        }

        // derive the name from the callable's method if needed
        if (!\is_string($name) || $name === '') {
            $name = $this->method;
        }



        $callableType = \is_callable($this->callable)
            ? CallableType::newFromCallable($this->callable, $name, $description)
            : CallableType::newFromReflectionMethod(
                $this->deriveCallable(),
                $this->buildReflectionMethod((string) $this->method),
                $this->classFqcn,
                $name,
                $description,
            );

        return $callableType !== null
            ? $callableType
            : throw ModexException::invalidCallable();
    }



    /**
     * Derive a callable from the callable (wraps it in a new callable if it's valid but not callable).
     *
     * @return callable
     * @throws ModexException If the callable is invalid.
     */
    public function deriveCallable(): callable
    {
        if (!$this->isValid()) {
            throw ModexException::invalidCallable();
        }

        // already callable
        if (\is_callable($this->callable)) {
            return $this->callable;
        }

        if (!$this->refersToClass) {
            throw ModexException::invalidCallable();
        }

        if (!\is_string($this->classFqcn)) {
            throw ModexException::invalidCallable();
        }

        if (!\is_string($this->method)) {
            throw ModexException::invalidCallable();
        }

        return new CallableClassMethodWrapper($this->classFqcn, $this->method);
    }
}
