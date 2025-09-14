<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal;

use DigitalAnomaly\AlteredLogic\Modex\Internal\ToolTypes\FunctionTool;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DTOs\FunctionCallDTO;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DTOs\FunctionCallResultDTO;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\FunctionCallResultsMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\UserMessage;
use DigitalAnomaly\AlteredLogic\Modex\ModexControl;

/**
 * Represents a call to a function tool.
 */
final class FunctionCallInstance
{
    /**
     * Constructor.
     *
     * @param FunctionTool    $tool   The tool to use.
     * @param FunctionCallDTO $call   The function-call details.
     * @param mixed           $result The result of the function call.
     */
    public function __construct(
        private FunctionTool $tool,
        private FunctionCallDTO $call,
        private mixed $result = null,
    ) {}



    /**
     * Call the function.
     *
     * @return mixed
     */
    public function call(): mixed
    {
        $result = $this->tool->callableType->callWithJsonParams($this->call->parametersJson);

        return $result instanceof ModexControl
            ? $result
            : $this->result = $this->normaliseCallResult($result);
    }

    /**
     * Normalise the function call result, ready to be passed back to the AI provider.
     *
     * This will cast non-string results to a JSON string.
     *
     * @param mixed $result The result of the function call.
     * @return string
     */
    private function normaliseCallResult(mixed $result): string
    {
        if (\is_string($result)) {
            return $result;
        }

        return (string) \json_encode($result, \JSON_PRETTY_PRINT);
    }



    /**
     * Build a user message from the function call result, ready to pass back to the AI provider.
     *
     * @return UserMessage
     */
    public function buildUserMessage(): UserMessage
    {
        $payload = new FunctionCallResultsMessagePayload([
            new FunctionCallResultDTO(
                $this->call->id,
                $this->tool->callableType->name,
                $this->call->parametersJson,
                $this->result,
            ),
        ]);

        return new UserMessage($payload);
    }
}
