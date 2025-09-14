<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessagePayloadInterface;

/**
 * A text message payload.
 */
final readonly class TextMessagePayload implements MessagePayloadInterface
{
    /**
     * Constructor.
     *
     * @todo - update properties to use PHP 8.4's get / set
     *
     * @param string $text The text of the message.
     */
    public function __construct(
        public string $text,
    ) {}



    /**
     * Check if the payload is empty.
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        return $this->text === '';
    }
}
