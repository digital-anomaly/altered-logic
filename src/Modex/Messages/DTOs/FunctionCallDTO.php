<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Messages\DTOs;

/**
 * A function call triggered by the AI provider.
 */
final class FunctionCallDTO
{
    /**
     * Constructor.
     *
     * @todo - update properties to use PHP 8.4's get / set
     *
     * @param string|null $id             The AI provider's id for the function call.
     * @param string      $name           The name of the function to call.
     * @param string      $parametersJson The parameters to pass to the function, indexed by name, in JSON format.
     */
    public function __construct(
        public ?string $id,
        public string $name,
        public string $parametersJson,
    ) {}



    // /**
    //  * Get the parameters.
    //  *
    //  * @todo - replace with PHP 8.4's get / set
    //  *
    //  * @return array<string,mixed>
    //  */
    // public function getParameters(): array
    // {
    //     $parameters = \json_decode($this->parametersJson, true);

    //     if (!\is_array($parameters)) {
    //         return [];
    //     }

    //     /** @var array<string,mixed> */
    //     return $parameters;
    // }
}
