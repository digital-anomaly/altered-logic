<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessageInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessagePayloadInterface;
use DigitalAnomaly\AlteredLogic\Modex\Messages\AiMessage;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DeveloperMessage;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\TextMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\UserMessage;

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
     * @param MessageInterface[] $previousMessages The messages that were previously sent.
     * @return void
     */
    public function __construct(array $previousMessages = [])
    {
        $this->allMessages = $previousMessages;
    }







    /**
     * Check if there are any unsent messages.
     *
     * @return boolean
     */
    public function hasUnsentMessages(): bool
    {
        return \count($this->unsentMessages) > 0;
    }







    /**
     * Specify the instructions to include in this one request.
     *
     * @todo - check that this gets forgotten for subsequent requests.
     *
     * @param string $instructions The instructions to set.
     * @return void
     */
    public function instructions(string $instructions): void
    {
        $this->instructions = $instructions;
    }







    /**
     * Add a message to the request.
     *
     * @param MessageInterface $message The message to add.
     * @return void
     */
    public function message(MessageInterface $message): void
    {
        $this->allMessages[] = $message;
        $this->unsentMessages[] = $message;
    }

    /**
     * Add multiples messages to the request.
     *
     * @param MessageInterface[] $messages The messages to add.
     * @return void
     */
    public function messages(array $messages): void
    {
        foreach ($messages as $message) {
            $this->message($message);
        }
    }



    /**
     * Add a developer message to the message list.
     *
     * @param string|MessagePayloadInterface|DeveloperMessage ...$prompt The prompt to add.
     * @return void
     */
    public function developerMessage(string|MessagePayloadInterface|DeveloperMessage ...$prompt): void
    {
        $messages = $this->buildMessages(DeveloperMessage::class, $prompt);

        $this->messages($messages);
    }

    /**
     * Add a user message to the message list.
     *
     * @param string|MessagePayloadInterface|UserMessage ...$prompt The prompt to add.
     * @return void
     */
    public function userMessage(string|MessagePayloadInterface|UserMessage ...$prompt): void
    {
        $messages = $this->buildMessages(UserMessage::class, $prompt);

        $this->messages($messages);
    }

    /**
     * Add an AI agent reply message to the message list.
     *
     * @param string|MessagePayloadInterface|AiMessage ...$reply The reply to add.
     * @return void
     */
    public function agentMessage(string|MessagePayloadInterface|AiMessage ...$reply): void
    {
        $messages = $this->buildMessages(AiMessage::class, $reply);

        $this->messages($messages);
    }

    /**
     * Build developer, user or AI agent messages from an array of prompts.
     *
     * @param class-string<MessageInterface>                         $messageClass The class of the message to create.
     * @param array<string|MessagePayloadInterface|MessageInterface> $inputs       The messages to add.
     * @return MessageInterface[]
     */
    private function buildMessages(string $messageClass, array $inputs): array
    {
        $messages = [];
        $payloads = [];
        foreach ($inputs as $input) {

            // already a message
            if ($input instanceof MessageInterface) {
                $messages[] = $input;
                continue;
            }

            // string, create a text payload for it
            if (\is_string($input)) {
                $payloads[] = new TextMessagePayload($input);
                continue;
            }

            // payload, so add it to the list
            if ($input instanceof MessagePayloadInterface) {
                $payloads[] = $input;
                continue;
            }

            throw new \Exception('Invalid prompt type: ' . \gettype($input)); // todo - add a custom exception.
        }

        if (\count($payloads) > 0) {
            $messages[] = new $messageClass($payloads);
        }

        return $messages;
    }
}
