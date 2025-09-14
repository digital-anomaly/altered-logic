<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\Internal;

use DigitalAnomaly\AlteredLogic\Common\HasDebugOutputTrait;
use DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission\EmbedTxnDTO;
use DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission\EmbedTxnInputDTO;
use DigitalAnomaly\AlteredLogic\Embed\Vector;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpTxnDTO;
use DigitalAnomaly\AlteredLogic\Support\StringHelper;

/**
 * Generates debug output for the EmbedExecutor.
 */
final class EmbedExecutorDebug
{
    use HasDebugOutputTrait;



    /**
     * Show debug information about a request.
     *
     * @param EmbedTxnInputDTO $embedInput The EmbedTxnInputDTO used.
     * @return void
     */
    public function showRequestSummaryDebug(EmbedTxnInputDTO $embedInput): void
    {
        if (!$this->debugLevelIsAtLeast(1)) {
            return;
        }



        $messages = [
            $this->debugBuildInputs($embedInput->inputs),
        ];
        $messages = \array_filter($messages, fn(string $message): bool => $message !== '');

        $this->debugInfo1a(\implode(\PHP_EOL . \PHP_EOL, $messages), "EMBED SUMMARY");
    }

    /**
     * Show the request body.
     *
     * @param string $requestBody The request body to display.
     * @return void
     */
    public function showRequestBodyDebug(string $requestBody): void
    {
        if (!$this->debugLevelIsAtLeast(2)) {
            return;
        }



        $bodyData = \json_decode($requestBody);
        $bodyData = \json_encode($bodyData, \JSON_PRETTY_PRINT);

        $messages = 'REQUEST BODY:'
            . \PHP_EOL
            . $bodyData;

        $this->debugInfo1b($messages, "EMBED");
    }







    /**
     * Show debug information about a response.
     *
     * @param array<string,Vector> $embeddings The embeddings to display.
     * @param HttpTxnDTO           $httpTxn    The HTTP transmission.
     * @param EmbedTxnDTO          $embedTxn   The multimodal transmission.
     * @return void
     */
    public function showResponseSummaryDebug(
        array $embeddings,
        HttpTxnDTO $httpTxn,
        EmbedTxnDTO $embedTxn,
    ): void {

        if (!$this->debugLevelIsAtLeast(1)) {
            return;
        }



        $messages = [
            $this->debugBuildHttpResponseInfoMessage($httpTxn, $embedTxn),
            $this->debugBuildResponseEmbeddings($embeddings),
        ];
        $messages = \array_filter($messages, fn(string $message): bool => $message !== '');
        $messages = \implode(\PHP_EOL . \PHP_EOL, $messages);

        $errorOccurred = ($httpTxn->response === null) || ($embedTxn->success === false);

        $errorOccurred
            ? $this->debugError1b($messages, "EMBED SUMMARY")
            : $this->debugInfo2a($messages, "EMBED SUMMARY");
    }

    /**
     * Show the response body.
     *
     * @param HttpTxnDTO  $httpTxn      The HTTP transmission.
     * @param EmbedTxnDTO $embedTxn     The multimodal transmission.
     * @param string      $responseBody The response body to display.
     * @return void
     */
    public function showResponseBodyDebug(
        HttpTxnDTO $httpTxn,
        EmbedTxnDTO $embedTxn,
        string $responseBody,
    ): void {

        if (!$this->debugLevelIsAtLeast(2)) {
            return;
        }



        $bodyData = \json_decode($responseBody, true);

        foreach ($bodyData['data'] ?? [] as $index => $embedData) {
            $coordinates = self::omitMiddleOfVector($embedData['embedding']);
            $bodyData['data'][$index]['embedding'] = $coordinates;
        }

        $bodyData = \json_encode($bodyData, \JSON_PRETTY_PRINT);
        $bodyData = self::unescapeTotalCoordinateMessage($bodyData);

        $messages = 'RESPONSE BODY:'
            . \PHP_EOL
            . $bodyData;

        $errorOccurred = ($httpTxn->response === null) || ($embedTxn->success === false);

        $errorOccurred
            ? $this->debugError1a($messages, "EMBED")
            : $this->debugInfo2b($messages, "EMBED");
    }







    /**
     * Show the faked embeddings.
     *
     * @param array<string,Vector> $fakedEmbeddings The faked embeddings to display.
     * @return void
     */
    public function showFakedEmbeddingsDebug(array $fakedEmbeddings): void
    {
        if (!$this->debugLevelIsAtLeast(1)) {
            return;
        }

        if (\count($fakedEmbeddings) === 0) {
            return;
        }



        $messages = [];
        foreach ($fakedEmbeddings as $key => $vector) {

            $key = StringHelper::truncate($key, 100);

            if ($this->debugLevelIsAtLeast(2)) {
                $coordinates = self::prettyPrintCoordinates($vector->coordinates());
                $messages[] = "\"{$key}\" = {$coordinates}";
            } else {
                $messages[] = "\"{$key}\"";
            }
        }
        $messages = \implode(\PHP_EOL, $messages);



        $this->debugLevelIsAtLeast(2)
            ? $this->debugInfo1b($messages, "EMBED FAKED")
            : $this->debugInfo1a($messages, "EMBED FAKED");
    }







    /**
     * Show the cached embeddings.
     *
     * @param array<string,Vector> $cachedEmbeddings The cached embeddings to display.
     * @return void
     */
    public function showCachedEmbeddingsDebug(array $cachedEmbeddings): void
    {
        if (!$this->debugLevelIsAtLeast(1)) {
            return;
        }

        if (\count($cachedEmbeddings) === 0) {
            return;
        }



        $messages = [];
        foreach ($cachedEmbeddings as $key => $vector) {

            $key = StringHelper::truncate($key, 100);

            if ($this->debugLevelIsAtLeast(2)) {
                $coordinates = self::prettyPrintCoordinates($vector->coordinates());
                $messages[] = "\"{$key}\" = {$coordinates}";
            } else {
                $messages[] = "\"{$key}\"";
            }
        }
        $messages = \implode(\PHP_EOL, $messages);



        $this->debugLevelIsAtLeast(2)
            ? $this->debugInfo1b($messages, "EMBED CACHE")
            : $this->debugInfo1a($messages, "EMBED CACHE");
    }







    /**
     * Build readable text from a set of inputs.
     *
     * @param string[] $inputs The inputs to display.
     * @return string
     */
    protected function debugBuildInputs(?array $inputs): string
    {
        if ($inputs === null) {
            return '';
        }

        if (\count($inputs) === 0) {
            return '';
        }

        $lines = [];
        foreach ($inputs as $input) {
            $lines[] = "INPUT: \"$input\"";
        }

        return \implode(\PHP_EOL, $lines);
    }







    /**
     * Build readable information about the HTTP response.
     *
     * @param HttpTxnDTO  $httpTxn  The HTTP transmission to display.
     * @param EmbedTxnDTO $embedTxn The multimodal transmission to display.
     * @return string
     */
    private function debugBuildHttpResponseInfoMessage(
        HttpTxnDTO $httpTxn,
        EmbedTxnDTO $embedTxn,
    ): string {

        if ($httpTxn->response === null) {
            dd($httpTxn, 'todo - return debug info instead of dd');
            return 'HTTP RESPONSE: ' . \PHP_EOL . '- No response';
        }



        $httpResponse = $httpTxn->response;
        $response = $embedTxn->response;



        $duration = $httpTxn->duration->durationSeconds !== null
            ? \round($httpTxn->duration->durationSeconds, 3) . ' seconds'
            : 'unknown';

        $resolvedModelExtra = $response?->resolvedModel !== '' && $response?->resolvedModel !== $embedTxn->model
            ? ' (actual: ' . $response?->resolvedModel . ')'
            : '';

        $tokens = $embedTxn->meta?->tokensUsed;

        $maxTokensExtra = $response?->maxTokensReached === true
            ? ' (MAX TOKENS REACHED)'
            : '';

        $lines = [];
        $lines[] = 'HTTP RESPONSE INFO:';
        $lines[] = "- Status: {$httpResponse?->statusCode} {$httpResponse?->statusReason}";
        $lines[] = "- Time:   {$duration}";
        $lines[] = "- Model:  {$embedTxn->provider} {$embedTxn->model}{$resolvedModelExtra}";
        if ($embedTxn->success === true) {
            $lines[] = "- Tokens: {$tokens?->inputTokens}{$maxTokensExtra}";
        }
        if ($response?->errorMessage !== null) {
            $lines[] = "- Error:  {$response?->errorMessage}";
        }
        if ($response?->errorDetails !== null) {
            $lines[] = "{$response?->errorDetails}";
        }

        return \implode(\PHP_EOL, $lines);
    }

    /**
     * Build readable information about the embeddings.
     *
     * @param array<string,Vector> $embeddingVectors The embeddings to display.
     * @return string
     */
    private function debugBuildResponseEmbeddings(?array $embeddingVectors): string
    {
        if ($embeddingVectors === null) {
            return '';
        }

        if (\count($embeddingVectors) === 0) {
            return '';
        }

        $embedLines = [];
        foreach ($embeddingVectors as $key => $embeddingVector) {

            $key = StringHelper::truncate($key, 100);
            $coordinates = self::prettyPrintCoordinates($embeddingVector->coordinates());
            $embedLines[] = "\"{$key}\" = {$coordinates}";
        }

        $embedLines = \implode(\PHP_EOL, $embedLines);

        return 'EMBEDDINGS: ' . \PHP_EOL . $embedLines;
    }









    /**
     * Generate a pretty version of the given coordinates.
     *
     * @param array<float> $coordinates The vector to update.
     * @return string
     */
    private static function prettyPrintCoordinates(array $coordinates): string
    {
        $coordinates = self::omitMiddleOfVector($coordinates);

        $coordinates = \json_encode($coordinates, \JSON_PRETTY_PRINT);

        return $coordinates !== false
            ? self::unescapeTotalCoordinateMessage($coordinates)
            : '';
    }

    /**
     * Unescape the total coordinate message.
     *
     * @param string $message The message to unescape.
     * @return string
     */
    private static function unescapeTotalCoordinateMessage(string $message): string
    {
        return (string) \preg_replace('/"(\(\.\.\. \d+ total coordinate(?:s)\))",?/', '$1', $message);
    }

    /**
     * Take the middle coordinates out of a vector, and replace them with '...'.
     *
     * @param array<float> $coordinates The vector to update.
     * @return array<float|string>
     */
    private static function omitMiddleOfVector(array $coordinates): array
    {
        $startCount = 5;
        $endCount = 0;

        $startVectors = \array_slice($coordinates, 0, $startCount);
        $endVectors = $endCount > 0
            ? \array_slice($coordinates, -$endCount)
            : [];

        $totalCount = \count($coordinates);
        $missingMessage = $totalCount === 1
            ? "(... $totalCount total coordinate)"
            : "(... $totalCount total coordinates)";

        $missingCount = \count($coordinates) - ($startCount + $endCount);
        // $missingMessage = $missingCount == 1
        //     ? "(... $missingCount coordinate omitted)"
        //     : "(... $missingCount coordinates omitted)";

        return $missingCount > 0
            ? \array_merge($startVectors, [$missingMessage], $endVectors)
            : $coordinates;
    }
}
