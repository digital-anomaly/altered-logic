<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Modex\Transformers;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessageInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessagePayloadInterface;
use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTxnOutputDTO;
use DigitalAnomaly\AlteredLogic\Modex\Messages\AiMessage;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DeveloperMessage;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DTOs\FunctionCallDTO;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\FunctionCallsMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\StructuredMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\TextMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\UserMessage;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpResponseDTO;
use Throwable;

/**
 * Interpret responses from the OpenAI Responses API.
 *
 * @see https://platform.openai.com/docs/api-reference/responses/create
 * @see https://platform.openai.com/docs/guides/text?api-mode=responses
 * @see https://platform.openai.com/docs/guides/function-calling?api-mode=responses
 * @see https://platform.openai.com/docs/guides/structured-outputs?api-mode=responses
 */
final class OpenAiResponsesApiInboundResponseTransformer
{
    /** @var boolean Whether the response contains a structured response or not. */
    private bool $isStructuredResponse;



    /**
     * Constructor.
     *
     * @param HttpResponseDTO|null $response     The response from OpenAI Responses API.
     * @param array<string,mixed>  $responseData The json-decoded response data from OpenAI Responses API.
     * @param boolean              $isWrapped    Whether the structured-output is wrapped in a class or not.
     */
    public function __construct(
        private ?HttpResponseDTO $response,
        private array $responseData,
        private bool $isWrapped,
    ) {
        $this->isStructuredResponse = ($responseData['text']['format']['type'] ?? null) === 'json_schema';
    }





    /**
     * Build the body the response from the OpenAI Responses API.
     *
     * @return ModexTxnOutputDTO|null
     */
    public function transformResponse(): ?ModexTxnOutputDTO
    {
        if ($this->response === null) {
            return null;
        }

        try {

            return $this->buildModexTxnOutputDTO();

        } catch (Throwable $e) {
            return null;
            // throw new \Exception('Failed to analyse response: ' . $e->getMessage(), 0, $e); // todo - throw a custom exception
        }
    }





    /**
     * Build a ModexTxnOutputDTO from the response.
     *
     * @return ModexTxnOutputDTO
     */
    private function buildModexTxnOutputDTO(): ModexTxnOutputDTO
    {
        $errorMessage = $errorDetails = null;
        if (($this->responseData['error'] ?? null) !== null) {
            $errorMessage = $this->responseData['error']['message'] ?? null;
            $errorDetails = \json_encode($this->responseData['error'] ?? null, \JSON_PRETTY_PRINT);
        }

        return new ModexTxnOutputDTO(
            $this->response->statusCode ?? 0,
            $this->response->statusReason ?? '',
            $this->responseData['model'] ?? '',
            $this->responseData['id'] ?? '',
            $this->wasMaxTokensReached($this->responseData),
            $errorMessage,
            $errorDetails,
            $this->buildMessages(),
        );
    }

    /**
     * Determine if the max-tokens were reached.
     *
     * @param array<string,mixed> $responseData The response data to determine if the max tokens were reached.
     * @return boolean
     */
    private function wasMaxTokensReached(array $responseData): bool
    {
        if (($responseData['status'] ?? null) !== 'incomplete') {
            return false;
        }

        if (($responseData['incomplete_details']['reason'] ?? null) !== 'max_output_tokens') {
            return false;
        }

        return true;
    }





    /**
     * Build messages from the response.
     *
     * @return MessageInterface[]
     */
    private function buildMessages(): array
    {
        $messagesData = (array) ($this->responseData['output'] ?? []);

        // if (count($messagesData) === 0) {
        //     throw new \Exception('No messages data found in response'); // todo - throw a custom exception
        // }

        $messages = [];
        foreach ($messagesData as $messageData) {
            $messages[] = $this->buildMessage($messageData);
        }

        return \array_filter($messages);
    }

    /**
     * Build a message from message data.
     *
     * @param array<string,mixed> $messageData The data to build the message from.
     * @return MessageInterface|null
     */
    private function buildMessage(array $messageData): ?MessageInterface
    {
        // [
        //   "type" => "message"
        //   "id" => "msg_0123456789abcdef0123456789abcdef0123456789abcdef"
        //   "status" => "completed"
        //   "role" => "assistant"
        //   "content" => array:1 [
        //     0 => array:3 [
        //       "type" => "output_text"
        //       "text" => "The capital of France is Paris."
        //       "annotations" => []
        //     ]
        //   ]
        // ]

        $type = (string) ($messageData['type'] ?? 'unknown');
        $payloads = $this->buildPayloads($type, $messageData);
        if (\count($payloads) === 0) {
            return null;
        }

        // $type === 'function_call'
        if ($type === 'function_call') {
            $id = (string) ($messageData['id'] ?? null);
            return new AiMessage($payloads, $id);
        }

        // $type === 'message'
        $role = (string) ($messageData['role'] ?? 'unknown');
        $id = (string) ($messageData['id'] ?? null);
        return match ($role) {
            'assistant' => new AiMessage($payloads, $id),
            'developer' => new DeveloperMessage($payloads, $id),
            'user' => new UserMessage($payloads, $id),
            default => throw new \Exception("Unknown message role: $role"), // todo - throw a custom exception
        };
    }

    /**
     * Build payloads from message data.
     *
     * @param string              $type        The type of message being built.
     * @param array<string,mixed> $messageData The data to build the payloads from.
     * @return MessagePayloadInterface[]
     */
    private function buildPayloads(string $type, array $messageData): array
    {
        $payloads = [];
        // $type === 'message'
        if ($type === 'message') {

            $payloadsData = (array) ($messageData['content'] ?? []);
            foreach ($payloadsData as $payloadData) {
                $payloads[] = $this->buildPayload($payloadData);
            }

        // $type === 'function_call'
        } elseif ($type === 'function_call') {
            $payloads[] = $this->buildToolCallsPayload($messageData);
        }

        return \array_filter($payloads);
    }

    /**
     * Build a payload from payload data.
     *
     * @param array<string,mixed> $payloadData The data to build the payload from.
     * @return MessagePayloadInterface|null
     */
    private function buildPayload(array $payloadData): ?MessagePayloadInterface
    {
        $payloadType = (string) ($payloadData['type'] ?? 'n/a');
        return match ($payloadType) {
            'output_text' => $this->buildTextPayload($payloadData),
            'function_call' => $this->buildToolCallsPayload($payloadData),
            default => throw new \Exception("Unknown payload type: $payloadType"), // todo - throw a custom exception
        };
    }

    /**
     * Build a text payload from payload data.
     *
     * @param array<string,mixed> $payloadData The data to build the text payload from.
     * @return MessagePayloadInterface|null
     */
    private function buildTextPayload(array $payloadData): ?MessagePayloadInterface
    {
        // array:3 [
        //   "type" => "output_text"
        //   "text" => "The capital of France is Paris."
        //   "annotations" => []
        // ]

        $text = (string) ($payloadData['text'] ?? '');
        if ($text === '') {
            return null;
        }

        // strip the outer class that wraps the array or enum
        if ($this->isWrapped) {

            $data = \json_decode($text, true);
            if (\is_array($data)) {
                $data = \reset($data);
                $text = \json_encode($data) ?: '';
            }
        }

        return $this->isStructuredResponse
            ? new StructuredMessagePayload(structuredJson: $text)
            : new TextMessagePayload($text);
    }

    /**
     * Build a tool calls payload from payload data.
     *
     * @param array<string,mixed> $payloadData The data to build the tool calls payload from.
     * @return MessagePayloadInterface|null
     */
    private function buildToolCallsPayload(array $payloadData): ?MessagePayloadInterface
    {
        // array:6 [
        //   "type" => "function_call"
        //   "id" => "fc_67f4a7d4b2a08192af9f1b252cb8904c0a4fdefad88ab7cc"
        //   "call_id" => "call_gAp8zfSEJYgNxjKLuk1k4rag"
        //   "name" => "get_current_weather"
        //   "arguments" => "{"country":"Australia","city":"Sydney"}"
        //   "status" => "completed"
        // ]

        $callId = (string) ($payloadData['call_id'] ?? '');
        $name = (string) ($payloadData['name'] ?? '');
        $arguments = (string) ($payloadData['arguments'] ?? '');
        // $status = (string) ($payloadData['status'] ?? '');

        $toolCallDTO = new FunctionCallDTO($callId, $name, $arguments);

        return new FunctionCallsMessagePayload([$toolCallDTO]);
    }
}
