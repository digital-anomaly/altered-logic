<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessageInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessagePayloadInterface;
use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTxnDTO;
use DigitalAnomaly\AlteredLogic\Modex\Internal\FunctionCallInstance;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexConnectionReference;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexDialogue;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ToolTypes\FunctionTool;
use DigitalAnomaly\AlteredLogic\Modex\Messages\AiMessage;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DeveloperMessage;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DTOs\FunctionCallDTO;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\FunctionCallsMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\TextMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\UserMessage;
use DigitalAnomaly\AlteredLogic\Modex\ModexControl;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpTxnDTO;

/**
 * Collects transmissions that belong to the same thread.
 *
 * Builds FunctionCallInstances and executes them.
 *
 * Collates the messages that have been sent and received.
 */
final class Thread
{
    /** @var HttpTxnDTO[] The HTTP transmissions. */
    private array $httpTxns = [];

    /** @var ModexTxnDTO[] The Modex transmissions. */
    private array $modexTxns = [];

    /** @var string Instructions to include in the next request only. */
    private string $instructions = '';

    /** @var MessageInterface[] All messages in the conversation (sent and received). */
    private array $messages = [];

    /** @var array<string,integer> Index of last "handled" message per connection - format [<fingerprint> => index]. */
    private array $lastHandledMessageIndexes = [];

    /** @var array<FunctionCallInstance[]> The function call instances. */
    private array $functionCallInstances = [];



    /**
     * Set instructions for the next request only.
     *
     * These instructions will be included in the next ModexDialogue built, then cleared after the request succeeds.
     *
     * @param string $instructions The instructions to set.
     * @return void
     */
    public function setInstructions(string $instructions): void
    {
        $this->instructions = $instructions;
    }

    /**
     * Get the current instructions.
     *
     * @return string
     */
    public function getInstructions(): string
    {
        return $this->instructions;
    }

    /**
     * Clear the instructions.
     *
     * @return void
     */
    public function clearInstructions(): void
    {
        $this->instructions = '';
    }



    /**
     * Add a message to the conversation.
     *
     * @param MessageInterface $message The message to add.
     * @return void
     */
    public function addMessage(MessageInterface $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * Add multiple messages to the conversation.
     *
     * @param MessageInterface[] $messages The messages to add.
     * @return void
     */
    public function addMessages(array $messages): void
    {
        foreach ($messages as $message) {
            $this->addMessage($message);
        }
    }

    /**
     * Add a developer message to the conversation.
     *
     * @param string|MessagePayloadInterface|DeveloperMessage ...$prompt The prompt to add.
     * @return void
     */
    public function developerMessage(string|MessagePayloadInterface|DeveloperMessage ...$prompt): void
    {
        $messages = $this->buildMessages(DeveloperMessage::class, $prompt);
        $this->addMessages($messages);
    }

    /**
     * Add a user message to the conversation.
     *
     * @param string|MessagePayloadInterface|UserMessage ...$prompt The prompt to add.
     * @return void
     */
    public function userMessage(string|MessagePayloadInterface|UserMessage ...$prompt): void
    {
        $messages = $this->buildMessages(UserMessage::class, $prompt);
        $this->addMessages($messages);
    }

    /**
     * Add an AI agent reply message to the conversation.
     *
     * @param string|MessagePayloadInterface|AiMessage ...$reply The reply to add.
     * @return void
     */
    public function agentMessage(string|MessagePayloadInterface|AiMessage ...$reply): void
    {
        $messages = $this->buildMessages(AiMessage::class, $reply);
        $this->addMessages($messages);
    }



    /**
     * Build developer, user or AI agent messages from an array of prompts.
     *
     * This is copied from ModexDialogue::buildMessages() to maintain the same behavior.
     *
     * @param class-string<MessageInterface>                         $messageClass The class of the message to create.
     * @param array<string|MessagePayloadInterface|MessageInterface> $inputs       The messages to add.
     * @return MessageInterface[]
     * @throws \Exception When an invalid prompt type is provided.
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
            // phpstan knows at this point that $input must be MessagePayloadInterface
            $payloads[] = $input;
        }

        if (\count($payloads) > 0) {
            $messages[] = new $messageClass($payloads);
        }

        return $messages;
    }



    /**
     * Get all messages in the conversation.
     *
     * @return MessageInterface[]
     */
    private function getAllMessages(): array
    {
        return $this->messages;
    }



    /**
     * Check if there are any unhandled messages for a specific connection (i.e. messages that haven't been sent yet).
     *
     * @param ModexConnectionReference $connectionReference Details about the connection used.
     * @return boolean
     */
    public function hasUnhandledMessages(ModexConnectionReference $connectionReference): bool
    {
        $connectionFingerprint = $connectionReference->fingerprint();
        $lastHandledMessageIndex = $this->lastHandledMessageIndexes[$connectionFingerprint] ?? -1;
        $lastMessageIndex = \count($this->messages) - 1;

        return $lastHandledMessageIndex < $lastMessageIndex;
    }

    /**
     * Get unhandled messages for a specific provider.
     *
     * Returns all messages that this provider hasn't seen yet.
     * If this provider hasn't seen any messages, returns all messages.
     *
     * @param ModexConnectionReference $connectionReference Details about the connection used.
     * @return MessageInterface[]
     */
    private function getUnhandledMessages(ModexConnectionReference $connectionReference): array
    {
        $connectionFingerprint = $connectionReference->fingerprint();

        // get the last handled index for this connection
        $lastHandledIndex = $this->lastHandledMessageIndexes[$connectionFingerprint] ?? null;
        if ($lastHandledIndex === null) {
            // connection hasn't seen any messages yet, so return all messages
            return $this->getAllMessages();
        }

        // return messages after the last handled index for this provider
        return \array_slice($this->messages, $lastHandledIndex + 1);
    }

    /**
     * Mark messages as handled for a specific provider up to the current message count.
     *
     * Called after successfully sending a request - marks all current messages as handled
     * for the specified provider.
     *
     * @param ModexConnectionReference $connectionReference Details about the connection used.
     * @return void
     */
    private function markCurrentMessagesAsHandled(ModexConnectionReference $connectionReference): void
    {
        // skip if there are no messages
        if (\count($this->messages) === 0) {
            return;
        }

        $connectionFingerprint = $connectionReference->fingerprint();
        $this->lastHandledMessageIndexes[$connectionFingerprint] = \count($this->messages) - 1;
    }



    /**
     * Build a ModexDialogue for the current conversation state, for a particular connection.
     *
     * The dialogue will contain:
     * - allMessages: complete conversation history
     * - unsentMessages: messages that haven't been handled by this provider yet
     * - instructions: instructions only for this request
     *
     * @param ModexConnectionReference $connectionReference Details about the connection used.
     * @return ModexDialogue
     */
    public function buildDialogue(ModexConnectionReference $connectionReference): ModexDialogue
    {
        return new ModexDialogue(
            $this->getAllMessages(),
            $this->getUnhandledMessages($connectionReference),
            $this->getInstructions()
        );
    }



    /**
     * Add a pair of HTTP transmission and multimodal transmission to the set.
     *
     * Also marks all current messages as handled (sent) for this provider, adds response
     * messages, and clears instructions.
     *
     * @param HttpTxnDTO               $httpTxn             The HTTP transmission to add.
     * @param ModexTxnDTO              $modexTxn            The multimodal transmission to add.
     * @param ModexConnectionReference $connectionReference Details about the connection used.
     * @return void
     */
    public function addTxn(
        HttpTxnDTO $httpTxn,
        ModexTxnDTO $modexTxn,
        ModexConnectionReference $connectionReference,
    ): void {

        // store the transactions
        $this->httpTxns[] = $httpTxn;
        $this->modexTxns[] = $modexTxn;
        $this->functionCallInstances[] = $this->buildFunctionCallInstances($modexTxn);

        // add response messages to the conversation
        foreach ($modexTxn->response->messages ?? [] as $message) {
            $this->addMessage($message);
        }

        // mark all messages as handled for THIS provider
        // (they were just sent to this provider, and the response messages were just received)
        $this->markCurrentMessagesAsHandled($connectionReference);

        // clear instructions (they were used in this request)
        $this->clearInstructions();
    }

    /**
     * Builds function call instances from the latest transmission.
     *
     * @param ModexTxnDTO $modexTxn The multimodal transmission to get the function call instances from.
     * @return array<FunctionCallInstance>
     */
    private function buildFunctionCallInstances(ModexTxnDTO $modexTxn): array
    {
        $functionCallInstances = [];

        foreach ($modexTxn->response->messages ?? [] as $message) {
            foreach ($message->getPayloads() as $payload) {

                if (!$payload instanceof FunctionCallsMessagePayload) {
                    continue;
                }

                foreach ($payload->calls as $functionCall) {

                    $functionTool = $modexTxn->request->schemas->availableTools[$functionCall->name] ?? null;

                    if (!$functionTool instanceof FunctionTool) {
                        continue;
                    }

                    $functionCallInstances[] = $this->buildFunctionCallInstance(
                        $functionTool,
                        $functionCall,
                    );
                }
            }
        }

        return $functionCallInstances;
    }

    /**
     * Build a function call instance.
     *
     * @param FunctionTool|null $functionTool        The tool to use.
     * @param FunctionCallDTO   $payloadFunctionCall The function call to build.
     * @return FunctionCallInstance
     * @throws \Exception When the function tool is not found.
     */
    private function buildFunctionCallInstance(
        ?FunctionTool $functionTool,
        FunctionCallDTO $payloadFunctionCall
    ): FunctionCallInstance {

        $functionName = $payloadFunctionCall->name;
        if ($functionTool === null) {
            throw new \Exception("Tool {$functionName} not found"); // todo - throw a custom exception
        }

        return new FunctionCallInstance(
            $functionTool,
            $payloadFunctionCall,
        );
    }





    /**
     * Resolve what the next step number will be.
     *
     * @return integer
     */
    public function nextStepNumber(): int
    {
        return \count($this->httpTxns) + 1;
    }

    /**
     * Resolve what the previous response id was.
     *
     * @return string|integer|null
     */
    public function prevResponseId(): string|int|null
    {
        if (\count($this->modexTxns) === 0) {
            return null;
        }

        $lastModexTxn = \end($this->modexTxns);

        return $lastModexTxn->response->providerId ?? null;
    }

    /**
     * Check if the modex has exhausted the max steps.
     *
     * @param integer|null $maxSteps The maximum number of steps to take.
     * @return boolean
     */
    public function hasExhaustedMaxSteps(?int $maxSteps): bool
    {
        if (($maxSteps !== null) && (\count($this->httpTxns) >= $maxSteps)) {
            return true;
        }

        return false;
    }





    /**
     * Get the latest Modex transmission.
     *
     * @return ModexTxnDTO|null
     */
    public function getLatestModexTxn(): ?ModexTxnDTO
    {
        if (\count($this->modexTxns) === 0) {
            return null;
        }

        return \end($this->modexTxns);
    }





    /**
     * Execute the new function calls.
     *
     * @return ModexControl|null
     */
    public function executeNewFunctionCalls(): ?ModexControl
    {
        $control = null;
        foreach ($this->getNewFunctionCallInstances() as $functionCallInstance) {

            $nextControl = $functionCallInstance->call();

            if ($nextControl instanceof ModexControl) {

                $control = $nextControl;

                // stop calling the function calls when the function call returned
                // a ModexControl and it wants to return a value right now
                if ($nextControl->hasReturn && $nextControl->returnNow) {
                    break;
                }
            }
        }

        return $control;
    }

    /**
     * Check if the latest response contained function calls.
     *
     * @return boolean
     */
    public function lastResponseContainedFunctionCalls(): bool
    {
        return \count($this->getNewFunctionCallInstances()) > 0;
    }

    /**
     * Get the new function call instances.
     *
     * @return FunctionCallInstance[]
     */
    public function getNewFunctionCallInstances(): array
    {
        $index = \count($this->functionCallInstances) - 1;

        return $this->functionCallInstances[$index] ?? [];
    }
}
