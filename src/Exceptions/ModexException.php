<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Exceptions;

use Throwable;

/**
 * Exceptions related to Modex.
 */
class ModexException extends AlteredLogicException
{
    /**
     * Thrown when the Modex is initialising and has a prepare-loop method.
     *
     * @param array<string> $methods The methods that were called during initialisation.
     * @return self
     */
    public static function disallowedInitMethodsCalledWhilePrepareLoopMethodExists(array $methods): self
    {
        $methodNames = [];
        foreach ($methods as $method) {
            $methodNames[] = "->{$method}()";
        }
        $methodNames = \implode(', ', $methodNames);

        return new self(
            "The following methods cannot be called when initialising the Modex if there is also a prepareLoop "
            . "method (because the settings are reset before calling it each time): {$methodNames}"
        );
    }


    // /**
    //  * Thrown when the Modex model profile could not be resolved.
    //  *
    //  * @param string|null $name The name of the model profile that could not be resolved.
    //  * @return self
    //  */
    // public static function cannotResolveModelProfile(?string $name): self
    // {
    //     return $name !== null
    //         ? new self("The modex model profile \"{$name}\" could not be resolved")
    //         : new self('The modex default model profile could not be resolved');
    // }

    /**
     * Thrown when the Modex API client could not be resolved.
     *
     * @param Throwable|null $previous The previous exception.
     * @return self
     */
    public static function modexApiClientCouldNotBeResolved(?Throwable $previous = null): self
    {
        return new self('The Modex API client could not be resolved', previous: $previous);
    }

    /**
     * Thrown when an invalid modex API client class is provided.
     *
     * @param string $className The invalid class name.
     * @return self
     */
    public static function invalidModexApiClient(string $className): self
    {
        return new self("Invalid modex API client class: {$className}");
    }



    /**
     * Thrown when HTTP requests are blocked and a faker didn't provide a response.
     *
     * @param string|null $model The model that was not found.
     * @return self
     */
    public static function httpRequestsAreBlocked(?string $model): self
    {
        return new self(
            $model !== null
                ? "Requests are blocked, and no faker was configured for model \"{$model}\""
                : "Requests are blocked, and no faker was configured",
        );
    }







    /**
     * General exception thrown when a callable is invalid.
     *
     * @return self
     */
    public static function invalidCallable(): self
    {
        return new self("The callable is invalid");
    }







    /**
     * Thrown when an object constructor is used as a definer.
     *
     * @param string $class The class that was being used.
     * @return self
     */
    public static function cannotUseObjectConstructorAsDefiner(string $class): self
    {
        return new self(
            "Cannot use the constructor of \"{$class}\" object as a definer "
            . "(it's already been instantiated)",
        );
    }

    /**
     * Thrown when an object's method that doesn't exist is used as a definer.
     *
     * @param string $class  The class that was being used.
     * @param string $method The method that was being used.
     * @return self
     */
    public static function cannotUseMissingObjectMethodAsDefiner(string $class, string $method): self
    {
        return new self(
            "The \"{$class}::{$method}()\" method does not exist, so cannot be used as a definer",
        );
    }

    /**
     * Thrown when an object's method that's not callable is used as a definer.
     *
     * @param string $class  The class that was being used.
     * @param string $method The method that was being used.
     * @return self
     */
    public static function cannotUseNonCallableObjectMethodAsDefiner(string $class, string $method): self
    {
        return new self(
            "The \"{$class}::{$method}()\" method is not callable, so cannot be used as a definer",
        );
    }



    /**
     * Thrown when a class that doesn't exist is used as a definer.
     *
     * @param string $class The class that was being used.
     * @return self
     */
    public static function cannotUseMissingClassAsDefiner(string $class): self
    {
        return new self("The \"{$class}\" class does not exist, so cannot be used as a definer");
    }

    /**
     * Thrown when a class with a non-callable constructor is used as a definer.
     *
     * @param string $class The class that was being used.
     * @return self
     */
    public static function cannotUseClassWithNonCallableConstructorAsDefiner(string $class): self
    {
        return new self("The \"{$class}\" constructor is not callable, so cannot be used as a definer");
    }

    /**
     * Thrown when a class method that doesn't exist is used as a definer.
     *
     * @param string $class  The class that was being used.
     * @param string $method The method that was being used.
     * @return self
     */
    public static function cannotUseMissingClassMethodAsDefiner(string $class, string $method): self
    {
        return new self(
            "The \"{$class}::{$method}()\" method does not exist, so cannot be used as a definer",
        );
    }

    /**
     * Thrown when a class method that's not callable is used as a definer.
     *
     * @param string $class  The class that was being used.
     * @param string $method The method that was being used.
     * @return self
     */
    public static function cannotUseNonCallableClassMethodAsDefiner(string $class, string $method): self
    {
        return new self(
            "The \"{$class}::{$method}()\" method is not callable, so cannot be used as a definer",
        );
    }

    /**
     * Thrown when a an invalid definer was given - not otherwise specified.
     *
     * @return self
     */
    public static function invalidDefiner(): self
    {
        return new self("The definer is invalid");
    }







    /**
     * Thrown when an object constructor is used as a loop orchestrator.
     *
     * @param string $class The class that was being used.
     * @return self
     */
    public static function cannotUseObjectConstructorAsLoopOrchestrator(string $class): self
    {
        return new self(
            "Cannot use the constructor of \"{$class}\" object as a loop orchestrator "
            . "(it's already been instantiated)",
        );
    }

    /**
     * Thrown when an object's method that doesn't exist is used as an loop orchestrator.
     *
     * @param string $class  The class that was being used.
     * @param string $method The method that was being used.
     * @return self
     */
    public static function cannotUseMissingObjectMethodAsLoopOrchestrator(string $class, string $method): self
    {
        return new self(
            "The \"{$class}::{$method}()\" method does not exist, so cannot be used as a loop orchestrator",
        );
    }

    /**
     * Thrown when an object's method that's not callable is used as a loop orchestrator.
     *
     * @param string $class  The class that was being used.
     * @param string $method The method that was being used.
     * @return self
     */
    public static function cannotUseNonCallableObjectMethodAsLoopOrchestrator(string $class, string $method): self
    {
        return new self(
            "The \"{$class}::{$method}()\" method is not callable, so cannot be used as a loop orchestrator",
        );
    }



    /**
     * Thrown when a class that doesn't exist is used as a loop orchestrator.
     *
     * @param string $class The class that was being used.
     * @return self
     */
    public static function cannotUseMissingClassAsLoopOrchestrator(string $class): self
    {
        return new self("The \"{$class}\" class does not exist, so cannot be used as a loop orchestrator");
    }

    /**
     * Thrown when a class with a non-callable constructor is used as an loop orchestrator.
     *
     * @param string $class The class that was being used.
     * @return self
     */
    public static function cannotUseClassWithNonCallableConstructorAsLoopOrchestrator(string $class): self
    {
        return new self("The \"{$class}\" constructor is not callable, so cannot be used as a loop orchestrator");
    }

    /**
     * Thrown when a class method that doesn't exist is used as a loop orchestrator.
     *
     * @param string $class  The class that was being used.
     * @param string $method The method that was being used.
     * @return self
     */
    public static function cannotUseMissingClassMethodAsLoopOrchestrator(string $class, string $method): self
    {
        return new self(
            "The \"{$class}::{$method}()\" method does not exist, so cannot be used as a loop orchestrator",
        );
    }

    /**
     * Thrown when a class method that's not callable is used as a loop orchestrator.
     *
     * @param string $class  The class that was being used.
     * @param string $method The method that was being used.
     * @return self
     */
    public static function cannotUseNonCallableClassMethodAsLoopOrchestrator(string $class, string $method): self
    {
        return new self(
            "The \"{$class}::{$method}()\" method is not callable, so cannot be used as a loop orchestrator",
        );
    }

    /**
     * Thrown when a an invalid loop orchestrator was given - not otherwise specified.
     *
     * @return self
     */
    public static function invalidLoopOrchestrator(): self
    {
        return new self("The loop orchestrator is invalid");
    }







    /**
     * Thrown when a class constructor is used as a tool.
     *
     * @param string $class The class that was being used.
     * @return self
     */
    public static function cannotUseConstructorAsTool(string $class): self
    {
        return new self(
            "Cannot use the constructor of class \"{$class}\" as a tool, as constructors don't return anything"
        );
    }



    /**
     * Thrown when an object constructor is used as a tool.
     *
     * @param string $class The class that was being used.
     * @return self
     */
    public static function cannotUseObjectConstructorAsTool(string $class): self
    {
        return new self(
            "Cannot use the constructor of \"{$class}\" object as a tool (it's already been instantiated)",
        );
    }

    /**
     * Thrown when an object's method that doesn't exist is used as a tool.
     *
     * @param string $class  The class that was being used.
     * @param string $method The method that was being used.
     * @return self
     */
    public static function cannotUseMissingObjectMethodAsTool(string $class, string $method): self
    {
        return new self(
            "The \"{$class}::{$method}()\" method does not exist, so cannot be used as a tool",
        );
    }

    /**
     * Thrown when an object's method that's not callable is used as a tool.
     *
     * @param string $class  The class that was being used.
     * @param string $method The method that was being used.
     * @return self
     */
    public static function cannotUseNonCallableObjectMethodAsTool(string $class, string $method): self
    {
        return new self("The \"{$class}::{$method}()\" method is not callable, so cannot be used as a tool");
    }



    /**
     * Thrown when a class that doesn't exist is used as a tool.
     *
     * @param string $class The class that was being used.
     * @return self
     */
    public static function cannotUseMissingClassAsTool(string $class): self
    {
        return new self("The \"{$class}\" class does not exist, so cannot be used as a tool");
    }

    /**
     * Thrown when a class with a non-callable constructor is used as a tool.
     *
     * @param string $class The class that was being used.
     * @return self
     */
    public static function cannotUseClassWithNonCallableConstructorAsTool(string $class): self
    {
        return new self("The \"{$class}\" constructor is not callable, so cannot be used as a tool");
    }

    /**
     * Thrown when a class method that doesn't exist is used as a tool.
     *
     * @param string $class  The class that was being used.
     * @param string $method The method that was being used.
     * @return self
     */
    public static function cannotUseMissingClassMethodAsTool(string $class, string $method): self
    {
        return new self("The \"{$class}::{$method}()\" method does not exist, so cannot be used as a tool");
    }

    /**
     * Thrown when a class method that's not callable is used as a tool.
     *
     * @param string $class  The class that was being used.
     * @param string $method The method that was being used.
     * @return self
     */
    public static function cannotUseNonCallableClassMethodAsTool(string $class, string $method): self
    {
        return new self(
            "The \"{$class}::{$method}()\" method is not callable, so cannot be used as a tool",
        );
    }

    /**
     * Thrown when a an invalid tool was given - not otherwise specified.
     *
     * @return self
     */
    public static function invalidTool(): self
    {
        return new self("The tool is invalid");
    }



    /**
     * Thrown when a tool name is not specified.
     *
     * @return self
     */
    public static function toolNameNotSpecified(): self
    {
        return new self('Please specify a name for the tool');
    }

    /**
     * Thrown when a tool name is not a key.
     *
     * @return self
     */
    public static function toolNameMustBeKey(): self
    {
        return new self("The key must be the tool's name");
    }







    /**
     * Thrown when an object constructor is used as a structured response.
     *
     * @param string $class The class that was being used.
     * @return self
     */
    public static function cannotUseObjectConstructorAsStructuredResponse(string $class): self
    {
        return new self(
            "Cannot use the constructor of \"{$class}\" object as a structured response "
            . "(it's already been instantiated)",
        );
    }

    /**
     * Thrown when an object's method that doesn't exist is used as a structured response.
     *
     * @param string $class  The class that was being used.
     * @param string $method The method that was being used.
     * @return self
     */
    public static function cannotUseMissingObjectMethodAsStructuredResponse(string $class, string $method): self
    {
        return new self(
            "The \"{$class}::{$method}()\" method does not exist, so cannot be used as a structured response",
        );
    }

    /**
     * Thrown when an object's method that's not callable is used as a structured response.
     *
     * @param string $class  The class that was being used.
     * @param string $method The method that was being used.
     * @return self
     */
    public static function cannotUseNonCallableObjectMethodAsStructuredResponse(string $class, string $method): self
    {
        return new self(
            "The \"{$class}::{$method}()\" method is not callable, so cannot be used as a structured response",
        );
    }



    /**
     * Thrown when a class that doesn't exist is used as a structured response.
     *
     * @param string $class The class that was being used.
     * @return self
     */
    public static function cannotUseMissingClassAsStructuredResponse(string $class): self
    {
        return new self("The \"{$class}\" class does not exist, so cannot be used as a structured response");
    }

    /**
     * Thrown when a class with a non-callable constructor is used as a structured response.
     *
     * @param string $class The class that was being used.
     * @return self
     */
    public static function cannotUseClassWithNonCallableConstructorAsStructuredResponse(string $class): self
    {
        return new self("The \"{$class}\" constructor is not callable, so cannot be used as a structured response");
    }

    /**
     * Thrown when a class method that doesn't exist is used as a structured response.
     *
     * @param string $class  The class that was being used.
     * @param string $method The method that was being used.
     * @return self
     */
    public static function cannotUseMissingClassMethodAsStructuredResponse(string $class, string $method): self
    {
        return new self(
            "The \"{$class}::{$method}()\" method does not exist, so cannot be used as a structured response",
        );
    }

    /**
     * Thrown when a class method that's not callable is used as a structured response.
     *
     * @param string $class  The class that was being used.
     * @param string $method The method that was being used.
     * @return self
     */
    public static function cannotUseNonCallableClassMethodAsStructuredResponse(string $class, string $method): self
    {
        return new self(
            "The \"{$class}::{$method}()\" method is not callable, so cannot be used as a structured response",
        );
    }

    /**
     * Thrown when a an invalid structured response was given - not otherwise specified.
     *
     * @return self
     */
    public static function invalidStructuredResponse(): self
    {
        return new self("The structured response is invalid");
    }



    /**
     * Thrown when a callable is used as a structured response that doesn't have any parameters.
     *
     * @return self
     */
    public static function callablesMustHaveAtLeastOneParameterAsStructuredResponse(): self
    {
        return new self('Callables being used as structured responses must have at least one parameter');
    }
}
