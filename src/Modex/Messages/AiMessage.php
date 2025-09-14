<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Messages;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessageInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessagePayloadInterface;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\FunctionCallsMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\StructuredMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\TextMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Traits\HasCommonMessageTrait;

/**
 * An "assistant" message (response from the AI provider) to use when interacting with a multimodal AI API.
 *
 * @see HasCommonMessageTrait
 */
final class AiMessage implements MessageInterface
{
    use HasCommonMessageTrait;



    // @todo - add image and audio payload types.
    /** @var array<class-string<MessagePayloadInterface>> The valid payload types. */
    protected const array VALID_PAYLOAD_TYPES = [
        FunctionCallsMessagePayload::class,
        StructuredMessagePayload::class,
        TextMessagePayload::class,
    ];
}
