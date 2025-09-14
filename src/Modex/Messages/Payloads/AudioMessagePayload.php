<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessagePayloadInterface;

/**
 * An audio message payload.
 *
 * @todo - review this and compare with the docs.
 */
final readonly class AudioMessagePayload implements MessagePayloadInterface
{
    /**
     * Constructor.
     *
     * @todo - update properties to use PHP 8.4's get / set
     *
     * @param string|null $base64 The audio, base64 encoded.
     * @param string|null $url    The URL of the audio.
     */
    public function __construct(
        public ?string $base64,
        public ?string $url,
    ) {}



    /**
     * Check if the payload is empty.
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        return $this->base64 === '';
    }
}
