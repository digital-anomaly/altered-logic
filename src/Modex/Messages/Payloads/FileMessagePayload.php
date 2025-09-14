<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessagePayloadInterface;

/**
 * A file message payload.
 */
final readonly class FileMessagePayload implements MessagePayloadInterface
{
    /**
     * Constructor.
     *
     * @todo - update properties to use PHP 8.4's get / set
     * @todo - auto-detect mime-type?
     *
     * @param string|null $base64   The file content, base64 encoded.
     * @param string|null $url      The URL of the file.
     * @param string|null $filename The filename of the file.
     * @param string      $mimeType The MIME type of the file.
     * @param string|null $detail   The detail to inspect the image with (low, high, auto).
     */
    public function __construct(
        public ?string $base64 = null,
        public ?string $url = null,
        public ?string $filename = null,
        public string $mimeType = '',
        public ?string $detail = null,
    ) {}



    /**
     * Check if the payload is empty.
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        if ($this->base64 !== null && $this->base64 !== '') {
            return false;
        }

        if ($this->url !== null && $this->url !== '') {
            return false;
        }

        return true;
    }
}
