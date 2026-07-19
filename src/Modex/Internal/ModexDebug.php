<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal;

use DigitalAnomaly\AlteredLogic\Common\HasDebugOutputTrait;
use DigitalAnomaly\AlteredLogic\Credentials\CredentialsOverride;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessageInterface;
use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTxnDTO;
use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTxnInputDTO;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexSchemas;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexSettings;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ToolTypes\FunctionTool;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ToolTypes\ServerSideTool;
use DigitalAnomaly\AlteredLogic\Modex\Messages\AiMessage;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DeveloperMessage;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\AudioMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\FileMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\FunctionCallResultsMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\FunctionCallsMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\ImageMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\StructuredMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\TextMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\UserMessage;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpTxnDTO;
use DigitalAnomaly\Schema\Schema;
use DigitalAnomaly\Schema\Types\ArrayType;
use DigitalAnomaly\Schema\Types\ClassType;
use DigitalAnomaly\Schema\Types\NativeType;
use ReflectionClass;

/**
 * Generates debug output for the Modex.
 */
final class ModexDebug
{
    use HasDebugOutputTrait;



    /**
     * Show debug information about a request.
     *
     * @param integer                  $requestNumber       The request number.
     * @param ModexTxnInputDTO         $modexInput          The ModexTxnInputDTO used.
     * @param string                   $credentialsName     The name of the credentials being used.
     * @param CredentialsOverride|null $credentialsOverride The override specified via ->credentials(), if any.
     * @param string                   $provider            The provider the model belongs to.
     * @return void
     */
    public function showRequestSummaryDebug(
        int $requestNumber,
        ModexTxnInputDTO $modexInput,
        string $credentialsName,
        ?CredentialsOverride $credentialsOverride,
        string $provider,
    ): void {

        if (!$this->debugLevelIsAtLeast(1)) {
            return;
        }



        $messages = [
            $this->debugBuildInstructionsMessage($modexInput->dialogue->instructions),
            $this->debugBuildMessages($modexInput->dialogue->unsentMessages),
            $this->debugBuildToolsMessage($modexInput->schemas),
            $this->debugBuildResponseTypeMessage($modexInput->schemas->structuredResponse),
            $this->debugBuildOtherSettingsMessage($modexInput->settings),
            $this->debugBuildCredentialsMessage($credentialsName, $credentialsOverride, $provider),
        ];
        $messages = \array_filter($messages, fn(string $message): bool => $message !== '');

        $this->debugInfo1a(\implode(\PHP_EOL . \PHP_EOL, $messages), "REQUEST {$requestNumber}");
    }

    /**
     * Show the request body.
     *
     * @param integer $requestNumber The request number.
     * @param string  $requestBody   The request body to display.
     * @return void
     */
    public function showRequestBodyDebug(int $requestNumber, string $requestBody): void
    {
        if (!$this->debugLevelIsAtLeast(2)) {
            return;
        }



        $bodyData = \json_decode($requestBody);
        $bodyData = \json_encode($bodyData, \JSON_PRETTY_PRINT);

        $messages = 'REQUEST BODY:'
            . \PHP_EOL
            . $bodyData;

        $this->debugInfo1b($messages, "REQUEST {$requestNumber}");
    }







    /**
     * Show debug information about a response.
     *
     * @param integer     $requestNumber The request number.
     * @param HttpTxnDTO  $httpTxn       The HTTP transmission.
     * @param ModexTxnDTO $modexTxn      The multimodal transmission.
     * @return void
     */
    public function showResponseDebug(
        int $requestNumber,
        HttpTxnDTO $httpTxn,
        ModexTxnDTO $modexTxn,
    ): void {

        if (!$this->debugLevelIsAtLeast(1)) {
            return;
        }



        $messages = [
            $this->debugBuildHttpResponseInfoMessage($httpTxn, $modexTxn),
            $this->debugBuildMessages($modexTxn->response?->messages),
        ];
        $messages = \array_filter($messages, fn(string $message): bool => $message !== '');
        $messages = \implode(\PHP_EOL . \PHP_EOL, $messages);

        $errorOccurred = ($httpTxn->response === null) || ($modexTxn->success === false);

        $errorOccurred
            ? $this->debugError1b($messages, "RESPONSE {$requestNumber}")
            : $this->debugInfo2a($messages, "RESPONSE {$requestNumber}");
    }

    /**
     * Show the response body.
     *
     * @param integer     $requestNumber The request number.
     * @param HttpTxnDTO  $httpTxn       The HTTP transmission.
     * @param ModexTxnDTO $modexTxn      The multimodal transmission.
     * @param string      $responseBody  The response body to print.
     * @return void
     */
    public function showResponseBodyDebug(
        int $requestNumber,
        HttpTxnDTO $httpTxn,
        ModexTxnDTO $modexTxn,
        string $responseBody,
    ): void {

        if (!$this->debugLevelIsAtLeast(2)) {
            return;
        }



        $bodyData = \json_decode($responseBody);
        $bodyData = \json_encode($bodyData, \JSON_PRETTY_PRINT);

        $messages = 'RESPONSE BODY:'
            . \PHP_EOL
            . $bodyData;

        $errorOccurred = ($httpTxn->response === null) || ($modexTxn->success === false);

        $errorOccurred
            ? $this->debugError1a($messages, "RESPONSE {$requestNumber}")
            : $this->debugInfo2b($messages, "RESPONSE {$requestNumber}");
    }







    /**
     * Build readable instructions.
     *
     * @param string $instructions The instructions to output.
     * @return string
     */
    protected function debugBuildInstructionsMessage(string $instructions): string
    {
        if ($instructions === '') {
            return '';
        }

        return 'INSTRUCTIONS:' . \PHP_EOL . $instructions;
    }

    /**
     * Build readable text from a set of messages.
     *
     * @param array<MessageInterface>|null $messages The messages to output.
     * @return string
     */
    protected function debugBuildMessages(?array $messages): string
    {
        if ($messages === null) {
            return '';
        }

        $lines = [];
        foreach ($messages as $message) {

            $role = match (true) {
                $message instanceof DeveloperMessage => 'DEVELOPER',
                $message instanceof UserMessage => 'USER',
                $message instanceof AiMessage => 'AGENT',
                default => 'UNKNOWN',
            };

            $payloadLines = [];
            foreach ($message->getPayloads() as $payload) {

                $payloadType = match (true) {
                    $payload instanceof AudioMessagePayload => '(AUDIO) ',
                    $payload instanceof FileMessagePayload => '(FILE) ',
                    $payload instanceof FunctionCallResultsMessagePayload => '', // rendered separately
                    $payload instanceof FunctionCallsMessagePayload => '', // rendered separately
                    $payload instanceof ImageMessagePayload => '(IMAGE) ',
                    $payload instanceof StructuredMessagePayload => '', // rendered separately
                    $payload instanceof TextMessagePayload => '(TEXT) ',
                    default => '(UNKNOWN) ',
                };



                $functionCallsCallback = function (FunctionCallsMessagePayload $payload): string {
                    $lines = [];
                    foreach ($payload->calls as $call) {
                        $json = \json_decode($call->parametersJson);
                        $json = \json_encode($json, \JSON_PRETTY_PRINT);
                        $lines[] = "- (CALL FUNCTION) {$call->name}:" . \PHP_EOL . "{$json}";
                    }
                    return \implode(\PHP_EOL, $lines);
                };

                $functionCallResponsesCallback = function (FunctionCallResultsMessagePayload $payload): string {

                    $lines = [];
                    foreach ($payload->results as $result) {
                        $lines[] = "- (FUNCTION CALL RESULT) {$result->name}:" . \PHP_EOL . "\"{$result->result}\"";
                    }
                    return \implode(\PHP_EOL, $lines);
                };

                $structuredCallback = function (StructuredMessagePayload $payload): string {
                    $json = \json_decode($payload->structuredJson);
                    $json = \json_encode($json, \JSON_PRETTY_PRINT);
                    return "- (STRUCTURED OUTPUT):" . \PHP_EOL . "{$json}";
                };



                $message = match (true) {
                    $payload instanceof AudioMessagePayload
                        => $payload->url
                            ?? 'base64 ' . \mb_substr((string) $payload->base64, 0, 20) . '…',
                    $payload instanceof FileMessagePayload
                        => $payload->filename
                            ?? $payload->url
                            ?? 'file ' . \mb_substr((string) $payload->base64, 0, 20) . '…',
                    $payload instanceof FunctionCallResultsMessagePayload
                        => $functionCallResponsesCallback($payload),
                    $payload instanceof FunctionCallsMessagePayload
                        => $functionCallsCallback($payload),
                    $payload instanceof ImageMessagePayload
                        => $payload->url
                            ?? 'base64 ' . \mb_substr((string) $payload->base64, 0, 20) . '…',
                    $payload instanceof StructuredMessagePayload
                        => $structuredCallback($payload),
                    $payload instanceof TextMessagePayload => $payload->text,
                    default => 'unknown',
                };

                $payloadLines[] = $payload instanceof TextMessagePayload
                    ? "\"$message\""
                    : "{$payloadType}{$message}";
            }

            $lines[] = "$role MESSAGE:" . \PHP_EOL
                . \implode(\PHP_EOL . \PHP_EOL, $payloadLines);
        }

        return \implode(\PHP_EOL . \PHP_EOL, $lines);
    }

    /**
     * Build readable information about the tools.
     *
     * @param ModexSchemas $schemas The ModexSchemas to output.
     * @return string
     */
    private function debugBuildToolsMessage(ModexSchemas $schemas): string
    {
        if ($schemas->callNoTools) {
            return 'TOOL SETTINGS:' . \PHP_EOL . '- call no tools';
        }



        $toolNames = [];
        foreach ($schemas->availableTools as $tool) {

            if (\count($schemas->onlyCallTools) > 0 && !\in_array($tool->getName(), $schemas->onlyCallTools, true)) {
                continue;
            }

            $parameters = [];
            if ($tool instanceof FunctionTool) {
                foreach ($tool->callableType->parameters as $paramName => $parameter) {

                    $type = $parameter->type instanceof NativeType
                        ? "{$parameter->type->type->value} "
                        : '';

                    $parameters[] = "$type\$$paramName";
                }
            }

            $extra = $tool instanceof ServerSideTool
                ? ' - SERVER SIDE TOOL'
                : '';

            $toolNames[] = \count($parameters) > 0
                ? '- ' . $tool->getName() . '(' . \implode(', ', $parameters) . ')' . $extra
                : '- ' . $tool->getName() . $extra;
        }



        if (\count($toolNames) === 0) {
            return '';
        }

        $settings = [];

        $settings[] = match (true) {
            $schemas->callAtLeastOneTool && $schemas->callOneToolAtMost => '- Call ONE tool',
            $schemas->callAtLeastOneTool => '- Call at least one tool',
            $schemas->callOneToolAtMost => '- Call one tool at most',
            default => '- Can call zero or more tools',
        };

        $settings[] = \count($schemas->onlyCallTools) > 0
            ? '- The AI provider will choose from: ' . \implode(', ', $schemas->onlyCallTools)
            : '- The AI provider will choose which';

        return 'TOOLS AVAILABLE: ' . \PHP_EOL . \implode(\PHP_EOL, $toolNames) . \PHP_EOL
            . \PHP_EOL
            . 'TOOL SETTINGS: ' . \PHP_EOL . \implode(\PHP_EOL, $settings);
    }

    /**
     * Build readable information about the structured response.
     *
     * @param Schema|null $structuredResponse The structured response to print.
     * @return string
     */
    private function debugBuildResponseTypeMessage(?Schema $structuredResponse): string
    {
        // Free-text
        if ($structuredResponse === null) {
            return 'RESPONSE TYPE REQUESTED: ' . \PHP_EOL . '- Free-text';
        }

        // ArrayType
        if ($structuredResponse->type instanceof ArrayType) {

            $types = [];

            $type = $structuredResponse->type->type;
            if ($type instanceof ClassType) {

                $properties = [];
                foreach ($type->children as $propertyName => $tempType) {

                    $isAnonymous = false;
                    if ($tempType->type instanceof ClassType) {
                        $reflectionClass = new ReflectionClass($tempType->type->fqcn);
                        $isAnonymous = $reflectionClass->isAnonymous();
                    }

                    $nullable = $tempType->nullable === true
                        ? '?'
                        : '';

                    $typeText = match (true) {
                        $tempType->type instanceof NativeType => "{$nullable}{$tempType->type->type->value} ",
                        $tempType->type instanceof ArrayType => "{$nullable}array ",
                        $tempType->type instanceof ClassType && $isAnonymous => "{$nullable}Anonymous-Class: ",
                        $tempType->type instanceof ClassType && !$isAnonymous => "{$nullable}{$tempType->type->fqcn} ",
                        default => '',
                    };

                    $properties[] = "$typeText\$$propertyName";
                }

                $types[] = \implode(', ', $properties);

            } else {

                $typeText = match (true) {
                    $type instanceof NativeType => "{$type->type->value} ",
                    $type instanceof ArrayType => 'array ',
                    // $type instanceof ClassType => "{$type->fqcn} ",
                    default => '',
                };

                if ($typeText !== '') {
                    $types[] = $typeText;
                }
            }

            return 'RESPONSE TYPE REQUESTED: ' . \PHP_EOL . '- Structured array of (' . \implode(', ', $types) . ')';

        // ClassType
        } elseif ($structuredResponse->type instanceof ClassType) {

            $properties = [];
            foreach ($structuredResponse->type->children as $propertyName => $type) {

                $nullable = $type->nullable === true
                    ? '?'
                    : '';
                $typeText = match (true) {
                    $type->type instanceof NativeType => "{$nullable}{$type->type->type->value} ",
                    $type->type instanceof ArrayType => "{$nullable}array ",
                    $type->type instanceof ClassType => "{$nullable}{$type->type->fqcn} ",
                    default => '',
                };

                $properties[] = "$typeText\$$propertyName";
            }

            return 'RESPONSE TYPE REQUESTED: ' . \PHP_EOL . '- Structured (' . \implode(', ', $properties) . ')';
        }

        return 'RESPONSE TYPE REQUESTED: ' . \PHP_EOL . '- Structured ???';
    }

    /**
     * Build readable information about other settings.
     *
     * @param ModexSettings $settings The settings to print.
     * @return string
     */
    private function debugBuildOtherSettingsMessage(ModexSettings $settings): string
    {
        $otherSettings = [];

        if ($settings->temperature !== null) {
            $otherSettings[] = "- temperature: {$settings->temperature}";
        }

        if ($settings->topP !== null) {
            $otherSettings[] = "- topP: {$settings->topP}";
        }

        if ($settings->maxOutputTokens !== null) {
            $extra = $settings->truncateResponse === true
                ? ' (truncate)'
                : ' (don\'t truncate - throw an error if the output is too long)';

                $otherSettings[] = "- max-output-tokens: {$settings->maxOutputTokens}{$extra}";
        }

        if ($settings->userIdentifier !== null) {
            $otherSettings[] = "- user-identifier: {$settings->userIdentifier}";
        }

        if (\count($otherSettings) === 0) {
            return '';
        }

        return 'OTHER SETTINGS: ' . \PHP_EOL . \implode(\PHP_EOL, $otherSettings);
    }

    /**
     * Build readable information about the credentials being used.
     *
     * Names only - no secret material, matching the codebase's auth-header masking posture.
     *
     * An override that was specified but doesn't cover this model's provider is called out explicitly - otherwise it's
     * indistinguishable from not having specified one at all, which is the symptom of a mistyped provider key.
     *
     * @param string                   $credentialsName     The name of the credentials being used.
     * @param CredentialsOverride|null $credentialsOverride The override specified via ->credentials(), if any.
     * @param string                   $provider            The provider the model belongs to.
     * @return string
     */
    private function debugBuildCredentialsMessage(
        string $credentialsName,
        ?CredentialsOverride $credentialsOverride,
        string $provider,
    ): string {

        $source = match (true) {
            $credentialsOverride === null => '(model default)',
            $credentialsOverride->pickCredentialsName($provider) !== null => '(override)',
            default => "(model default - the ->credentials() override doesn't cover provider '{$provider}')",
        };

        return 'CREDENTIALS: ' . \PHP_EOL . "- '{$credentialsName}' {$source}";
    }







    /**
     * Build readable information about the HTTP response.
     *
     * @param HttpTxnDTO  $httpTxn  The HTTP transmission to print.
     * @param ModexTxnDTO $modexTxn The multimodal transmission to print.
     * @return string
     */
    private function debugBuildHttpResponseInfoMessage(HttpTxnDTO $httpTxn, ModexTxnDTO $modexTxn): string
    {
        if ($httpTxn->response === null) {
            \dd($httpTxn, 'todo - return debug info instead of dd');
            return 'HTTP RESPONSE: ' . \PHP_EOL . '- No response';
        }



        $httpResponse = $httpTxn->response;
        $response = $modexTxn->response;



        $duration = $httpTxn->duration->durationSeconds !== null
            ? \round($httpTxn->duration->durationSeconds, 3) . ' seconds'
            : 'unknown';

        $provider = $modexTxn->connectionReference->getProviderString();
        $model = $modexTxn->connectionReference->getModel();

        $resolvedModelExtra = '';
        if ($response?->resolvedModel !== '') {
            $resolvedModelExtra = $response?->resolvedModel !== $modexTxn->connectionReference->getModel()
                ? " (actual: {$response?->resolvedModel})"
                : '';
        }

        $tokens = $modexTxn->meta?->tokensUsed;

        $cachedTokens = [];
        $cachedTokens[] = $tokens?->cachedInputTokens !== 0
            ? "{$tokens?->cachedInputTokens} input"
            : '';
        $cachedTokens[] = $tokens?->cachedOutputTokens !== 0
            ? "{$tokens?->cachedOutputTokens} output"
            : '';
        $cachedTokens = \array_filter($cachedTokens, fn(string $token): bool => $token !== '');
        $cachedTokens = \count($cachedTokens) > 0
            ? ' (of those ' . \implode(' and ', $cachedTokens) . ' were cached)'
            : '';

        $maxTokensExtra = $response?->maxTokensReached === true
            ? ' (MAX TOKENS REACHED)'
            : '';

        $lines = [];
        $lines[] = 'HTTP RESPONSE INFO:';
        $lines[] = "- Status: {$httpResponse->statusCode} {$httpResponse->statusReason}";
        $lines[] = "- Time:   {$duration}";
        $lines[] = "- Model:  {$provider} {$model}{$resolvedModelExtra}";
        if ($modexTxn->success === true) {
            $lines[] = "- Tokens: {$tokens?->totalTokens} "
                . "({$tokens?->inputTokens} input, {$tokens?->outputTokens} output)"
                . "{$cachedTokens}{$maxTokensExtra}";
        }
        if ($response?->errorMessage !== null) {
            $lines[] = "- Error:  {$response->errorMessage}";
        }
        if ($response?->errorDetails !== null) {
            $lines[] = "{$response->errorDetails}";
        }

        return \implode(\PHP_EOL, $lines);
    }
}
