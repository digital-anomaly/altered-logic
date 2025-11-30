<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Modex\Transformers;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessageInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessagePayloadInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Schemas\ToolInterface;
use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTxnInputDTO;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexDialogue;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexSchemas;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexSettings;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ToolTypes\ServerSideTool;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DeveloperMessage;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\FileMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\FunctionCallResultsMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\FunctionCallsMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\ImageMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\StructuredMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\TextMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\UserMessage;
use DigitalAnomaly\AlteredLogic\Support\ArrayHelper;
use DigitalAnomaly\Schema\Schema;
use DigitalAnomaly\Schema\Types\ClassType;

/**
 * Build the body for requests to send to the OpenAI Responses API.
 */
final class OpenAiResponsesApiOutboundRequestTransformer
{
    /**
     * Build the body of a request to the OpenAI Responses API.
     *
     * @see https://platform.openai.com/docs/api-reference/responses/create
     * @see https://platform.openai.com/docs/guides/text?api-mode=responses
     * @see https://platform.openai.com/docs/guides/function-calling?api-mode=responses
     * @see https://platform.openai.com/docs/guides/structured-outputs?api-mode=responses
     *
     * @param ModexTxnInputDTO           $modexInput     The ModexTxnInputDTO to use for the request.
     * @param string                     $model          The model to use for the request.
     * @param array<class-string,string> $messageRoles   The roles for the messages.
     * @param string|integer|null        $prevResponseId The id of the previous response, for conversation continuation.
     * @param string|null                $wrapSuffix     The suffix to wrap the top level of the structured-output in.
     * @return string
     */
    public static function buildRequestBody(
        ModexTxnInputDTO $modexInput,
        string $model,
        array $messageRoles,
        string|int|null $prevResponseId,
        ?string $wrapSuffix,
    ): string {

        $settings = $modexInput->settings;
        $schemas = $modexInput->schemas;
        $dialogue = $modexInput->dialogue;

        $bodyData = [

            // 'input' and 'model' are required

            'input' => self::buildInputMessages($dialogue, $messageRoles),
            'model' => $model,

            // the rest are optional
            // (and are in alphabetical from here on down. the order doesn't matter but it matches the docs)

            // 'include' => ..
            'instructions' => $dialogue->instructions,
            'max_output_tokens' => self::resolveMaxOutputTokens($settings),
            // 'metadata' => ..
            'parallel_tool_calls' => self::resolveParallelToolCalls($schemas),
            'previous_response_id' => $prevResponseId,
            // 'prompt_cache_key' => ..
            // 'reasoning' => ..
            // 'safety_identifier' => ..
            'store' => true, // record history to refer to in subsequent requests
            // 'stream' => false, // default false
            'temperature' => $settings->temperature, // default 1
            'text' => self::buildResponseSchema($schemas->structuredResponse, $wrapSuffix),
            'tool_choice' => self::resolveToolChoice($schemas),
            'tools' => self::resolveAvailableTools($schemas),
            'top_p' => $settings->topP, // default 1
            'truncation' => self::resolveTruncateOutput($settings), // doesn't seem to work?
            // 'user' => $settings->userIdentifier, // todo - remove (deprecated)
        ];

        $bodyData = ArrayHelper::removeEmptyElements($bodyData);

        return (string) \json_encode($bodyData);
    }







    /**
     * Resolve the max tokens for the request.
     *
     * @param ModexSettings $settings The ModexSettings to use for the request.
     * @return integer|null
     */
    private static function resolveMaxOutputTokens(ModexSettings $settings): ?int
    {
        return $settings->maxOutputTokens !== null
            ? \max(16, $settings->maxOutputTokens) // the minimum for OpenAI Responses API is 16
            : null;
    }

    /**
     * Resolve whether to truncate the response output or not.
     *
     * @param ModexSettings $settings The ModexSettings to use for the request.
     * @return string|null
     */
    private static function resolveTruncateOutput(ModexSettings $settings): ?string
    {
        $maxOutputTokens = self::resolveMaxOutputTokens($settings);

        if ($maxOutputTokens === null) {
            return null;
        }

        // when not set (null), it's equivalent to 'disabled'
        return match ($settings->truncateResponse) {
            true => 'auto',
            false => 'disabled', // return an error when the response is too long
            default => null,     // return an error when the response is too long
        };
    }







    /**
     * Resolve the available tools for the request.
     *
     * @param ModexSchemas $structures The ModexStructures to use for the request.
     * @return array<mixed>
     */
    private static function resolveAvailableTools(ModexSchemas $structures): array
    {
        // don't define any if tool calls are disallowed
        if ($structures->callNoTools) {
            return [];
        }

        $tools = self::resolvePossibleTools($structures);

        $availableTools = [];
        foreach ($tools as $tool) {
            $availableTools[] = OpenAiResponsesApiOutboundStructuredDataTransformer::transformTool($tool);
        }

        return $availableTools;
    }

    /**
     * Resolve the tools that may be used.
     *
     * @param ModexSchemas $structures The ModexStructures to use for the request.
     * @return array<string,ToolInterface>
     */
    private static function resolvePossibleTools(ModexSchemas $structures): array
    {
        if ($structures->onlyCallTools === []) {
            return $structures->availableTools;
        }

        return \array_filter(
            $structures->availableTools,
            fn (ToolInterface $tool) => \in_array($tool->getName(), $structures->onlyCallTools, true),
        );
    }

    /**
     * Resolve the tool choice for the request.
     *
     * @param ModexSchemas $structures The ModexStructures to use for the request.
     * @return array<string,mixed>|string|null
     */
    private static function resolveToolChoice(ModexSchemas $structures): string|array|null
    {
        if ($structures->callNoTools) {
            return 'none';
        }

        $tools = self::resolvePossibleTools($structures);
        if (\count($tools) === 0) {
            return null;
        }



        // check if a particular tool has been specified
        if (\count($structures->onlyCallTools) > 0) {

            if (\count($structures->onlyCallTools) > 1) {
                throw new \Exception('Only one tool can be specified at most (onlyCallTools)'); // todo - throw a custom exception
            }

            $toolName = \head($structures->onlyCallTools);
            $tool = $tools[$toolName] ?? null;

            if ($tool !== null) {
                return match (true) {
                    $tool instanceof ServerSideTool => ['type' => $tool->getName()],
                    // $tool instanceof FunctionTool => ['name' => $tool->name, 'type' => 'function'],
                    default => ['name' => $tool->getName(), 'type' => 'function'], // FunctionTool
                };
            }

            return ['name' => $tool, 'type' => 'function'];
        }



        // zero          none / false/true
        // zero-or-one   auto / false
        // zero-or-more  auto / true
        // one           required / false
        // one-or-more   required / true

        // none / allowed-one / allowed-many / required-one / required-many

        // ->callNoTools()        - tool_choice = none
        // ->callAtLeastOneTool() - tool_choice = required (default: auto)
        // ->callAtMostOneTool()  - parallel_tool_calls = false (default: true)

        return $structures->callAtLeastOneTool
            ? 'required'
            : 'auto';
    }

    /**
     * Resolve whether parallel tool calls are allowed for the request.
     *
     * @param ModexSchemas $schemas The ModexSchemas to use for the request.
     * @return boolean|null
     */
    private static function resolveParallelToolCalls(ModexSchemas $schemas): ?bool
    {
        if ($schemas->callNoTools) {
            return null;
        }

        $tools = self::resolvePossibleTools($schemas);
        if (\count($tools) === 0) {
            return null;
        }

        // ->callNoTools()        - tool_choice = none
        // ->callAtLeastOneTool() - tool_choice = required (default: auto)
        // ->callAtMostOneTool()  - parallel_tool_calls = false (default: true)

        return !$schemas->callOneToolAtMost;
    }

    /**
     * Build the schema for a structured response.
     *
     * @param Schema|null $schema     The structured response to use.
     * @param string|null $wrapSuffix The suffix to wrap the top level of the structured-output in.
     * @return array<string,mixed>
     */
    private static function buildResponseSchema(?Schema $schema, ?string $wrapSuffix): array
    {
        if ($schema === null) {
            return ['format' => ['type' => 'text']];
        }



        // the top level cannot be an array, an enum, or a scalar type

        // if it is one of these, wrap it in a class
        // the returned values are unwrapped when being processed

        if ($wrapSuffix !== null) {

            $oldSchema = $schema;

            $schema = new Schema(
                ClassType::newAnonymous(["{$oldSchema->name}" => $oldSchema]),
                "{$oldSchema->name}{$wrapSuffix}",
                // $oldSchema->description, //
            );
        }



        $schemaJson = OpenAiResponsesApiOutboundStructuredDataTransformer::transformStructuredResponse($schema);
        if ($schemaJson['properties'] === []) {
            throw new \Exception('Callables being used as structured responses must have at least one parameter'); // todo - throw a custom exception
        }



        return [
            'format' => [
                'type' => 'json_schema',
                'name' => $schema->name,
                'schema' => $schemaJson,
            ]
        ];
    }







    /**
     * Build the input (messages) for the request.
     *
     * @param ModexDialogue              $dialogue     The ModexDialogue to use for the request.
     * @param array<class-string,string> $messageRoles The roles for the messages.
     * @return string|array<mixed>
     */
    private static function buildInputMessages(ModexDialogue $dialogue, array $messageRoles): string|array
    {
        return self::buildSingleUserTextMessageJson($dialogue)
            ?? self::buildMultipleMessageJson($dialogue, $messageRoles)
            ?? '';
    }

    /**
     * Build the json for a single user text message (if that's what we have).
     *
     * @param ModexDialogue $dialogue The ModexDialogue to use for the request.
     * @return string|null
     */
    private static function buildSingleUserTextMessageJson(ModexDialogue $dialogue): ?string
    {
        if (!self::messagesAreASingleUserTextMessage($dialogue->unsentMessages)) {
            return null;
        }

        /** @var UserMessage $message */
        $message = $dialogue->unsentMessages[0];

        /** @var TextMessagePayload $payload */
        $payload = $message->getPayloads()[0];

        return $payload->text;
    }

    /**
     * Build the json for all of the messages.
     *
     * @param ModexDialogue              $dialogue     The ModexDialogue to use for the request.
     * @param array<class-string,string> $messageRoles The roles for the messages.
     * @return array<mixed>|null
     */
    private static function buildMultipleMessageJson(ModexDialogue $dialogue, array $messageRoles): ?array
    {
        $json = [];
        foreach ($dialogue->unsentMessages as $message) {

            $isInput = $message instanceof DeveloperMessage || $message instanceof UserMessage;

            // skip it if it has no payloads
            $payloads = $message->getPayloads();
            if (\count($payloads) === 0) {
                continue;
            }

            // separate the function call result payloads from the rest
            $functionCallCallback = fn ($payload) => $payload instanceof FunctionCallsMessagePayload;
            $functionCallResultCallback = fn ($payload) => $payload instanceof FunctionCallResultsMessagePayload;
            $allOtherCallback = fn ($payload)
                => !$functionCallCallback($payload) && !$functionCallResultCallback($payload);

            $functionCallPayloads = \array_filter($payloads, $functionCallCallback);
            $functionCallResultPayloads = \array_filter($payloads, $functionCallResultCallback);
            $allOtherPayloads = \array_filter($payloads, $allOtherCallback);

            // don't wrap function call payloads
            $functionCallJson = self::buildJsonForMultiplePayloads($functionCallPayloads, $isInput);
            $json = \array_merge($json, $functionCallJson);

            // don't wrap function call result payloads
            $functionCallResultJson = self::buildJsonForMultiplePayloads($functionCallResultPayloads, $isInput);
            $json = \array_merge($json, $functionCallResultJson);

            // wrap the rest in [role = 'xx', content = 'xx']
            $content = self::buildSingleTextPayloadJson($allOtherPayloads)
                ?? self::buildJsonForMultiplePayloads($allOtherPayloads, $isInput);

            if ($content !== []) {
                $role = self::determineMessageRole($message, $messageRoles);
                $json[] = [
                    'role' => $role,
                    'content' => $content,
                ];
            }
        }

        return \count($json) > 0
            ? $json
            : null;
    }

    /**
     * Build the json for a single text payload (if that's what we have).
     *
     * @param MessagePayloadInterface[] $payloads The payloads to build.
     * @return string|null
     */
    private static function buildSingleTextPayloadJson(array $payloads): ?string
    {
        if (
            !self::payloadsAreASingleTextMessage($payloads)
            && !self::payloadsAreASingleStructuredMessage($payloads)
        ) {
            return null;
        }

        /** @var TextMessagePayload|StructuredMessagePayload $payload */
        $payload = $payloads[0];

        return $payload instanceof TextMessagePayload
            ? $payload->text
            : $payload->structuredJson; // Note: see below

        // Note: OpenAI Responses API does not currently have a way of representing a structured output when it's used
        //       as input as part of earlier conversation being passed back to the model.
        //       @see https://platform.openai.com/docs/api-reference/responses/create#responses_create-input
        //       As a fallback, this code builds a regular text payload and adds the structured response json as the
        //       text instead
    }

    /**
     * Build the json for multiple payloads.
     *
     * @param MessagePayloadInterface[] $payloads The payloads to build.
     * @param boolean                   $isInput  Whether the payloads are for an input or output message.
     * @return array<mixed>
     */
    private static function buildJsonForMultiplePayloads(array $payloads, bool $isInput): array
    {
        $return = [];
        foreach ($payloads as $payload) {
            $return = \array_merge($return, self::buildSinglePayloadJson($payload, $isInput));
        }

        return $return;
    }

    /**
     * Build the json for a single message.
     *
     * Some types (FunctionCallResultsMessagePayload) may contain several sets of data to return. So this method returns
     * an array them.
     *
     * @param MessagePayloadInterface $payload The payload to build.
     * @param boolean                 $isInput Whether the payload is for an input or output message.
     * @return array<integer,array<string,mixed>>
     */
    private static function buildSinglePayloadJson(MessagePayloadInterface $payload, bool $isInput): array
    {
        // Supported values are: 'input_text', 'input_image', 'output_text', 'refusal', 'input_file', 'computer_screenshot', and 'summary_text'

        // TextMessagePayload
        if ($payload instanceof TextMessagePayload) {

            $return = [
                'type' => $isInput ? 'input_text' : 'output_text',
                'text' => $payload->text,
            ];
            return [$return];
        }

        // StructuredMessagePayload
        // Note: OpenAI Responses API does not currently have a way of representing a structured output when it's used
        //       as input as part of earlier conversation being passed back to the model.
        //       @see https://platform.openai.com/docs/api-reference/responses/create#responses_create-input
        //       As a fallback, this code builds a regular text payload and adds the structured response json as the
        //       text instead
        if ($payload instanceof StructuredMessagePayload) {

            $return = [
                'type' => $isInput ? 'input_text' : 'output_text',
                'text' => $payload->structuredJson,
            ];
            return [$return];
        }

        // FunctionCallsMessagePayload
        if ($payload instanceof FunctionCallsMessagePayload) {

            // may contain several results
            $return = [];
            foreach ($payload->calls as $call) {
                $return[] = [
                    'type' => 'function_call',
                    'call_id' => $call->id,
                    'name' => $call->name,
                    'arguments' => $call->parametersJson,
                ];
            }

            return $return;
        }

        // FunctionCallResultsMessagePayload
        if ($payload instanceof FunctionCallResultsMessagePayload) {

            // may contain several results
            $return = [];
            foreach ($payload->results as $result) {
                $return[] = [
                    'type' => 'function_call_output',
                    'call_id' => $result->id,
                    'output' => $result->result,
                ];
            }

            return $return;
        }

        // ImageMessagePayload
        if ($payload instanceof ImageMessagePayload) {

            if ($payload->base64 !== null) {

                $return = [
                    'type' => $isInput ? 'input_image' : 'output_image',
                    'image_url' => "data:{$payload->mimeType};base64,{$payload->base64}",
                    'detail' => $payload->detail,
                ];

                $return = ArrayHelper::removeEmptyElements($return, ['detail']);
                return [$return];
            }

            if ($payload->url !== null) {

                $return = [
                    'type' => $isInput ? 'input_image' : 'output_image',
                    'image_url' => $payload->url,
                    'detail' => $payload->detail,
                ];

                $return = ArrayHelper::removeEmptyElements($return, ['detail']);
                return [$return];
            }
        }

        // FileMessagePayload
        if ($payload instanceof FileMessagePayload) {

            if ($payload->base64 !== null) {

                $return = [
                    'type' => $isInput ? 'input_file' : 'output_file',
                    'file_data' => "data:{$payload->mimeType};base64,{$payload->base64}",
                    'filename' => $payload->filename,
                    'detail' => $payload->detail,
                ];

                $return = ArrayHelper::removeEmptyElements($return, ['detail', 'filename']);
                return [$return];
            }

            if ($payload->url !== null) {
                throw new \Exception('File urls are not implemented'); // todo - throw a custom exception
            }
        }

        // todo - add other payload types
        throw new \Exception('need to add support for payload type: ' . \get_class($payload));
    }





    /**
     * Check if the messages contain only one single user text message.
     *
     * @param MessageInterface[] $messages The messages to check.
     * @return boolean
     */
    private static function messagesAreASingleUserTextMessage(array $messages): bool
    {
        if (\count($messages) !== 1) {
            return false;
        }

        $message = $messages[0];
        if (!$message instanceof UserMessage) {
            return false;
        }

        return self::payloadsAreASingleTextMessage($message->getPayloads());
    }

    /**
     * Check if the payloads contain only one single text payload.
     *
     * @param MessagePayloadInterface[] $payloads The payloads to check.
     * @return boolean
     */
    private static function payloadsAreASingleTextMessage(array $payloads): bool
    {
        if (\count($payloads) !== 1) {
            return false;
        }

        $payload = $payloads[0];
        if (!$payload instanceof TextMessagePayload) {
            return false;
        }

        return true;
    }

    /**
     * Check if the payloads contain only one single structured payload.
     *
     * @param MessagePayloadInterface[] $payloads The payloads to check.
     * @return boolean
     */
    private static function payloadsAreASingleStructuredMessage(array $payloads): bool
    {
        if (\count($payloads) !== 1) {
            return false;
        }

        $payload = $payloads[0];
        if (!$payload instanceof StructuredMessagePayload) {
            return false;
        }

        return true;
    }

    /**
     * Get the role for a message.
     *
     * @param MessageInterface           $message      The message to get the role for.
     * @param array<class-string,string> $messageRoles The roles for the messages.
     * @return string
     */
    private static function determineMessageRole(MessageInterface $message, array $messageRoles): string
    {
        return $messageRoles[\get_class($message)];
    }
}
