<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Messages;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessageInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessagePayloadInterface;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\FileMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\FunctionCallResultsMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\ImageMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\TextMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Traits\HasCommonMessageTrait;

/**
 * A "user" message to use when interacting with a multimodal AI API.
 *
 * @see HasCommonMessageTrait
 */
final class UserMessage implements MessageInterface
{
    use HasCommonMessageTrait;



    // @todo - add image and audio payload types.
    /** @var array<class-string<MessagePayloadInterface>> The valid payload types. */
    protected const array VALID_PAYLOAD_TYPES = [
        ImageMessagePayload::class,
        FileMessagePayload::class,
        FunctionCallResultsMessagePayload::class,
        TextMessagePayload::class,
    ];
}
