<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Messages\DTOs;

/**
 * The result of a function call, to pass back to the LLM.
 */
final class FunctionCallResultDTO
{
    /**
     * Constructor.
     *
     * @todo - update properties to use PHP 8.4's get / set
     *
     * @param string|null $id             The AI provider's id for the function call.
     * @param string      $name           The name of the function to call.
     * @param string      $parametersJson The parameters passed to the function, indexed by name, in JSON format.
     * @param mixed       $result         The result of the function call.
     */
    public function __construct(
        public ?string $id,
        public string $name,
        public string $parametersJson,
        public mixed $result,
    ) {
        $this->result = $this->result ?? '';
    }



    /**
     * Get the parameters.
     *
     * @todo - replace with PHP 8.4's get / set
     *
     * @return array<string,mixed>
     */
    public function getParameters(): array
    {
        $parameters = \json_decode($this->parametersJson, true);

        if (!\is_array($parameters)) {
            return [];
        }

        /** @var array<string,mixed> */
        return $parameters;
    }
}
