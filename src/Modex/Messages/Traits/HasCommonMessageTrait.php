<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Messages\Traits;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessageInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessagePayloadInterface;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DTOs\FunctionCallDTO;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DTOs\FunctionCallResultDTO;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\AudioMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\FunctionCallResultsMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\FunctionCallsMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\ImageMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\TextMessagePayload;

/**
 * A trait to add common message methods to a class.
 *
 * @see MessageInterface
 */
trait HasCommonMessageTrait
{
    /** @var string|null The AI provider's id for the message. */
    private ?string $id = null;

    /** @var MessagePayloadInterface[] The message payloads (text, images etc). */
    private array $payloads = [];



    /**
     * Constructor.
     *
     * @todo - add image and audio payload types.
     *
     * @param MessagePayloadInterface|string|array<MessagePayloadInterface|string> $payload The message's payload/s.
     * @param string|null                                                          $id      The AI provider's msg id.
     */
    public function __construct(MessagePayloadInterface|string|array $payload, ?string $id = null)
    {
        $this->id = $id;
        $this->storePayloads($payload, self::VALID_PAYLOAD_TYPES); // this const is present in each message class
                                                                   // (AiMessage, DeveloperMessage, UserMessage)
    }





    /**
     * Set the id.
     *
     * @param string|null $id The id.
     * @return void
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * Get the id.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }





    /**
     * Add payloads to the message.
     *
     * @param MessagePayloadInterface|string|array<MessagePayloadInterface|string> $payload The payload/s to add.
     * @return void
     */
    public function addPayload(MessagePayloadInterface|string|array $payload): void
    {
        $this->storePayloads($payload, self::VALID_PAYLOAD_TYPES);
    }

    /**
     * Add multiple payloads.
     *
     * @param MessagePayloadInterface|string|array<MessagePayloadInterface|string> $payload           Payloads to add.
     * @param array<class-string<MessagePayloadInterface>>                         $validPayloadTypes The valid types.
     * @return void
     */
    private function storePayloads(MessagePayloadInterface|string|array $payload, array $validPayloadTypes): void
    {
        $payloads = \is_array($payload)
            ? $payload
            : [$payload];

        foreach ($payloads as $tempPayload) {
            $this->storePayload($tempPayload, $validPayloadTypes);
        }
    }

    /**
     * Add a payload if it is valid.
     *
     * @param MessagePayloadInterface|string               $payload           The payload to add.
     * @param array<class-string<MessagePayloadInterface>> $validPayloadTypes The valid payload types.
     * @return void
     */
    private function storePayload(MessagePayloadInterface|string $payload, array $validPayloadTypes): void
    {
        if (\is_string($payload)) {
            $payload = new TextMessagePayload($payload);
        }

        if (!$this->typeIsValid($payload, $validPayloadTypes)) {
            // todo - use a custom exception
            throw new \Exception('Invalid payload type - valid type/s: ' . \implode(', ', $validPayloadTypes));
        }

        // don't store empty payloads
        if ($payload->isEmpty()) {
            return;
        }

        $this->payloads[] = $payload;
    }

    /**
     * Check if the payload type is valid.
     *
     * @param MessagePayloadInterface                      $payload           The payload to check.
     * @param array<class-string<MessagePayloadInterface>> $validPayloadTypes The valid payload types.
     * @return boolean
     */
    private function typeIsValid(MessagePayloadInterface $payload, array $validPayloadTypes): bool
    {
        foreach ($validPayloadTypes as $validPayloadType) {
            if ($payload instanceof $validPayloadType) {
                return true;
            }
        }

        return false;
    }





    /**
     * Get the payloads.
     *
     * @return MessagePayloadInterface[]
     */
    public function getPayloads(): array
    {
        return $this->payloads;
    }





    /**
     * Check if the message is empty.
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        return \count($this->payloads) === 0;
    }

    // /**
    //  * Check if the message contains multiple payloads.
    //  *
    //  * @return boolean
    //  */
    // public function containsMultiplePayloads(): bool
    // {
    //     return \count($this->payloads) > 1;
    // }



    // /**
    //  * Check if the message contains a text payload.
    //  *
    //  * @return boolean
    //  */
    // public function containsTextPayload(): bool
    // {
    //     foreach ($this->payloads as $payload) {
    //         if ($payload instanceof TextMessagePayload) {
    //             return true;
    //         }
    //     }

    //     return false;
    // }

    // /**
    //  * Get the text.
    //  *
    //  * @return string
    //  */
    // public function getText(): string
    // {
    //     return $this->payload instanceof TextMessagePayload
    //         ? $this->payload->text
    //         : '';
    // }



    // /**
    //  * Check if the message contains an image payload.
    //  *
    //  * @return boolean
    //  */
    // public function containsImagePayload(): bool
    // {
    //     foreach ($this->payloads as $payload) {
    //         if ($payload instanceof ImageMessagePayload) {
    //             return true;
    //         }
    //     }

    //     return false;
    // }

    // /**
    //  * Get the image.
    //  *
    //  * @todo
    //  *
    //  * @return string
    //  */
    // public function getImage(): string
    // {
    //     return $this->payload instanceof ImageMessagePayload
    //         ? $this->payload->image
    //         : '';
    // }



    // /**
    //  * Check if the message contains an audio payload.
    //  *
    //  * @return boolean
    //  */
    // public function containsAudioPayload(): bool
    // {
    //     foreach ($this->payloads as $payload) {
    //         if ($payload instanceof AudioMessagePayload) {
    //             return true;
    //         }
    //     }

    //     return false;
    // }

    // /**
    //  * Get the audio.
    //  *
    //  * @todo
    //  *
    //  * @return string
    //  */
    // public function getAudio(): string
    // {
    //     return $this->payload instanceof AudioMessagePayload
    //         ? $this->payload->audio
    //         : '';
    // }



    /**
     * Check if the message contains function calls.
     *
     * @return boolean
     */
    public function containsFunctionCalls(): bool
    {
        foreach ($this->payloads as $payload) {
            if ($payload instanceof FunctionCallsMessagePayload) {
                return true;
            }
        }

        return false;
    }

    // /**
    //  * Get the function calls.
    //  *
    //  * @return FunctionCallDTO[]
    //  */
    // public function getFunctionCalls(): array
    // {
    //     return $this->payload instanceof FunctionCallsMessagePayload
    //         ? $this->payload->functionCalls
    //         : [];
    // }



    /**
     * Check if the message contains function call results.
     *
     * @return boolean
     */
    public function containsFunctionCallResults(): bool
    {
        foreach ($this->payloads as $payload) {
            if ($payload instanceof FunctionCallResultsMessagePayload) {
                return true;
            }
        }

        return false;
    }

    // /**
    //  * Get the function call results.
    //  *
    //  * @return FunctionCallResultDTO[]
    //  */
    // public function getFunctionCallResults(): array
    // {
    //     return $this->payload instanceof FunctionCallResultsMessagePayload
    //         ? $this->payload->functionCallResults
    //         : [];
    // }
}
