<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessageInterface;

/**
 * Container for instructions and messages to include in a Modex request.
 */
final class ModexDialogue
{
    /** @var string The instructions to include in this one request. */
    public private(set) string $instructions = '';

    /** @var MessageInterface[] All messages, sent or unsent. */
    public private(set) array $allMessages = [];

    /** @var MessageInterface[] The messages that haven't been sent yet. */
    public private(set) array $unsentMessages = [];



    /**
     * Constructor.
     *
     * @param MessageInterface[] $allMessages    All messages (sent and received).
     * @param MessageInterface[] $unsentMessages Messages that haven't been sent yet.
     * @param string             $instructions   Ephemeral instructions for this request.
     * @return void
     */
    public function __construct(
        array $allMessages = [],
        array $unsentMessages = [],
        string $instructions = ''
    ) {
        $this->allMessages = $allMessages;
        $this->unsentMessages = $unsentMessages;
        $this->instructions = $instructions;
    }
}
