<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessagePayloadInterface;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DTOs\FunctionCallDTO;

/**
 * A message to use when interacting with a multimodal AI provider.
 */
interface MessageInterface
{
    /**
     * Constructor.
     *
     * @param MessagePayloadInterface|string|array<MessagePayloadInterface|string> $payload The message's payload/s.
     * @param string|null                                                          $id      The AI provider's msg id.
     */
    public function __construct(MessagePayloadInterface|string|array $payload, ?string $id = null);





    /**
     * Set the id.
     *
     * @param string|null $id The id.
     * @return void
     */
    public function setId(?string $id): void;

    /**
     * Get the id.
     *
     * @return string|null
     */
    public function getId(): ?string;





    /**
     * Add payloads to the message.
     *
     * @param MessagePayloadInterface|array<MessagePayloadInterface> $payload The payload/s to add.
     * @return void
     */
    public function addPayload(MessagePayloadInterface|array $payload): void;

    /**
     * Get the payloads.
     *
     * @return MessagePayloadInterface[]
     */
    public function getPayloads(): array;





    /**
     * Check if the message is empty.
     *
     * @return boolean
     */
    public function isEmpty(): bool;

    // /**
    //  * Check if the message contains multiple payloads.
    //  *
    //  * @return boolean
    //  */
    // public function containsMultiplePayloads(): bool;





    // /**
    //  * Check if the message contains a text payload.
    //  *
    //  * @return boolean
    //  */
    // public function containsTextPayload(): bool;

    // /**
    //  * Get the text.
    //  *
    //  * @return string
    //  */
    // public function getText(): string;





    // /**
    //  * Check if the message contains an image payload.
    //  *
    //  * @return boolean
    //  */
    // public function containsImagePayload(): bool;

    // /**
    //  * Get the image.
    //  *
    //  * @todo
    //  *
    //  * @return string
    //  */
    // public function getImage(): string;





    // /**
    //  * Check if the message contains an audio payload.
    //  *
    //  * @return boolean
    //  */
    // public function containsAudioPayload(): bool;

    // /**
    //  * Get the audio.
    //  *
    //  * @todo
    //  *
    //  * @return string
    //  */
    // public function getAudio(): string;





    /**
     * Check if the message contains function calls.
     *
     * @return boolean
     */
    public function containsFunctionCalls(): bool;

    // /**
    //  * Get the function calls.
    //  *
    //  * @return FunctionCallDTO[]
    //  */
    // public function getFunctionCalls(): array;





    /**
     * Check if the message contains function call results.
     *
     * @return boolean
     */
    public function containsFunctionCallResults(): bool;

    // /**
    //  * Get the function call results.
    //  *
    //  * @return FunctionCallResultDTO[]
    //  */
    // public function getFunctionCallResults(): array;
}
