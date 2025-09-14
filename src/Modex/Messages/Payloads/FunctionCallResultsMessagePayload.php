<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessagePayloadInterface;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DTOs\FunctionCallResultDTO;

/**
 * A function-call results message payload.
 */
final readonly class FunctionCallResultsMessagePayload implements MessagePayloadInterface
{
    /**
     * Constructor.
     *
     * @todo - update properties to use PHP 8.4's get / set
     *
     * @param FunctionCallResultDTO[] $results The results from function calls.
     */
    public function __construct(
        public array $results,
    ) {}



    /**
     * Check if the payload is empty.
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        return \count($this->results) === 0;
    }
}
