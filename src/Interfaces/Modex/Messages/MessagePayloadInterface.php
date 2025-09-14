<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages;

/**
 * A payload used in a message when interacting with a multimodal AI provider.
 */
interface MessagePayloadInterface
{
    /**
     * Check if the payload is empty.
     *
     * @return boolean
     */
    public function isEmpty(): bool;
}
