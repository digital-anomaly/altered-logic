<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal\ToolTypes;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Schemas\ToolInterface;
use DigitalAnomaly\AlteredLogic\Modex\Internal\SchemaModifier;
use DigitalAnomaly\Schema\Types\CallableType;

/**
 * Represents a Function (an AI function call), that an AI provider can utilise.
 *
 * This includes it's name, description, parameters (nested deeply) and return type.
 */
final readonly class FunctionTool implements ToolInterface
{
    /**
     * Constructor.
     *
     * @param CallableType $callableType The CallableType, contains the callable details.
     */
    public function __construct(public CallableType $callableType)
    {
        // perform post-processing to the parameters
        foreach ($callableType->parameters as $parameter) {
            SchemaModifier::addNativePHPClassComments($parameter);
        }
    }

    /**
     * Build from a callable.
     *
     * @param callable|object|array{0:class-string|object,1:string}|class-string|string $callable The callable to build
     *                                                                                            the tool for.
     * @param string                                                                    $name     The name of the tool.
     * @return self
     */
    public static function newFromCallable(callable|object|array|string $callable, string $name): self
    {
        $resolvedType = CallableType::newFromCallable($callable, $name);
        if ($resolvedType === null) {
            throw new \InvalidArgumentException('Invalid tool type: ' . \gettype($callable)); // todo - add a custom exception.
        }

        return new self($resolvedType);
    }





    /**
     * Get the name of the tool, this identifies it to the AI provider.
     *
     * @return string The name of the tool type.
     */
    public function getName(): string
    {
        return $this->callableType->readableName();
    }
}
