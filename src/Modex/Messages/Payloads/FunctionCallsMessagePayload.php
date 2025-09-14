<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessagePayloadInterface;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DTOs\FunctionCallDTO;

/**
 * A function-calls message payload.
 */
final readonly class FunctionCallsMessagePayload implements MessagePayloadInterface
{
    /**
     * Constructor.
     *
     * @todo - update properties to use PHP 8.4's get / set
     *
     * @param FunctionCallDTO[] $calls The function calls to be made.
     */
    public function __construct(
        public array $calls,
    ) {}



    /**
     * Check if the payload is empty.
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        return \count($this->calls) === 0;
    }
}
