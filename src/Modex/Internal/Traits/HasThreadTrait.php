<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal\Traits;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessageInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessagePayloadInterface;
use DigitalAnomaly\AlteredLogic\Modex\Internal\Thread;
use DigitalAnomaly\AlteredLogic\Modex\Messages\AiMessage;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DeveloperMessage;
use DigitalAnomaly\AlteredLogic\Modex\Messages\UserMessage;

/**
 * Trait that provides methods to configure the instructions and messages for a Modex request.
 *
 * Messages and instructions are managed by the Thread.
 */
trait HasThreadTrait
{
    /** @var Thread|null The Thread instance for managing conversation state. */
    private ?Thread $thread = null;



    /**
     * Get the Thread instance.
     *
     * @return Thread
     */
    private function getThread(): Thread
    {
        return $this->thread ??= new Thread();
    }



    /**
     * Specify the instructions to include in this one request.
     *
     * @param string $instructions The instructions to set.
     * @return self
     */
    public function instructions(string $instructions): self
    {
        $this->getThread()->setInstructions($instructions);

        return $this;
    }



    /**
     * Add a message to the request.
     *
     * @param MessageInterface $message The message to add.
     * @return self
     */
    public function message(MessageInterface $message): self
    {
        $this->getThread()->addMessage($message);

        return $this;
    }

    /**
     * Add multiples messages to the request.
     *
     * @param MessageInterface[] $messages The messages to add.
     * @return self
     */
    public function messages(array $messages): self
    {
        $this->getThread()->addMessages($messages);

        return $this;
    }



    /**
     * Add a developer message to the message list.
     *
     * @param string|MessagePayloadInterface|DeveloperMessage ...$prompt The prompt to add.
     * @return self
     */
    public function developerMessage(string|MessagePayloadInterface|DeveloperMessage ...$prompt): self
    {
        $this->getThread()->developerMessage(...$prompt);

        return $this;
    }

    /**
     * Add a user message to the message list.
     *
     * @param string|MessagePayloadInterface|UserMessage ...$prompt The prompt to add.
     * @return self
     */
    public function userMessage(string|MessagePayloadInterface|UserMessage ...$prompt): self
    {
        $this->getThread()->userMessage(...$prompt);

        return $this;
    }

    /**
     * Add an AI agent reply message to the message list.
     *
     * @param string|MessagePayloadInterface|AiMessage ...$reply The reply to add.
     * @return self
     */
    public function agentMessage(string|MessagePayloadInterface|AiMessage ...$reply): self
    {
        $this->getThread()->agentMessage(...$reply);

        return $this;
    }
}
