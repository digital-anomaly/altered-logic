<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal\Traits;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessageInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessagePayloadInterface;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexDialogue;
use DigitalAnomaly\AlteredLogic\Modex\Messages\AiMessage;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DeveloperMessage;
use DigitalAnomaly\AlteredLogic\Modex\Messages\UserMessage;

/**
 * Trait that provides methods to configure the instructions and messages for a Modex request.
 *
 * @todo DEPRECATION: This trait is being phased out. Use HasThreadTrait instead.
 *       Thread now manages messages directly. This trait will be removed in a future version.
 *       Do not use this trait in new code.
 *
 * @deprecated Use HasThreadTrait instead
 */
trait HasModexDialogueTrait
{
    /** @var ModexDialogue The ModexDialogue instance for the Modex request. */
    private ModexDialogue $dialogue;



    /**
     * Get the ModexDialogue instance.
     *
     * @return ModexDialogue
     */
    protected function getDialogue(): ModexDialogue
    {
        return $this->dialogue ??= new ModexDialogue();
    }

    /**
     * Reset the ModexDialogue instance - for use when continuing the conversation, so it starts with a fresh set of
     * messages.
     *
     * @param boolean $keepAllMessageList Whether to keep the list of existing $allMessages or not.
     * @return void
     */
    protected function resetDialogue(bool $keepAllMessageList = true): void
    {
        $allMessages = $keepAllMessageList
            ? $this->getDialogue()->allMessages
            : [];

        $this->dialogue = new ModexDialogue($allMessages);
    }







    /**
     * Specify the instructions to include in this one request.
     *
     * @param string $instructions The instructions to set.
     * @return self
     */
    public function instructions(string $instructions): self
    {
        $this->getDialogue()->instructions($instructions);

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
        $this->getDialogue()->message($message);

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
        $this->getDialogue()->messages($messages);

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
        $this->getDialogue()->developerMessage(...$prompt);

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
        $this->getDialogue()->userMessage(... $prompt);

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
        $this->getDialogue()->agentMessage(...$reply);

        return $this;
    }
}
