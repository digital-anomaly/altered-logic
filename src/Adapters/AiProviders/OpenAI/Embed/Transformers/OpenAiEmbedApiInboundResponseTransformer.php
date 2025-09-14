<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Embed\Transformers;

use DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission\EmbedTxnOutputDTO;
use DigitalAnomaly\AlteredLogic\Embed\Vector;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpResponseDTO;
use Throwable;

/**
 * Interpret responses from the OpenAI Embeddings API.
 *
 * @see https://platform.openai.com/docs/guides/embeddings
 * @see https://platform.openai.com/docs/api-reference/embeddings
 */
final class OpenAiEmbedApiInboundResponseTransformer
{
    /**
     * Constructor.
     *
     * @param HttpResponseDTO|null $response     The response from OpenAI Embeddings API.
     * @param array<string,mixed>  $responseData The json-decoded response data from OpenAI Embeddings API.
     */
    public function __construct(
        private ?HttpResponseDTO $response,
        private array $responseData,
    ) {}





    /**
     * Build the body the response from the OpenAI Embeddings API.
     *
     * @return EmbedTxnOutputDTO|null
     */
    public function transformResponse(): ?EmbedTxnOutputDTO
    {
        if ($this->response === null) {
            return null;
        }

        try {

            return $this->buildEmbedTxnOutputDTO();

        } catch (Throwable $e) {
            return null;
            // throw new \Exception('Failed to analyse response: ' . $e->getMessage(), 0, $e); // todo - throw a custom exception
        }
    }





    /**
     * Build a EmbedTxnOutputDTO from the response.
     *
     * @return EmbedTxnOutputDTO
     */
    private function buildEmbedTxnOutputDTO(): EmbedTxnOutputDTO
    {
        $errorMessage = $errorDetails = null;
        if (($this->responseData['error'] ?? null) !== null) {
            $errorMessage = $this->responseData['error']['message'] ?? null;
            $errorDetails = \json_encode($this->responseData['error'] ?? null, \JSON_PRETTY_PRINT);
        }

        return new EmbedTxnOutputDTO(
            $this->response->statusCode ?? 0,
            $this->response->statusReason ?? '',
            $this->responseData['model'] ?? '',
            $this->wasMaxTokensReached($this->responseData),
            $errorMessage,
            $errorDetails,
            $this->buildEmbeddingVectors(),
        );
    }

    /**
     * Determine if the max-tokens were reached.
     *
     * @todo - test this
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
     * @return array<integer,Vector|null>
     */
    private function buildEmbeddingVectors(): array
    {
        if (($this->responseData['object'] ?? null) !== 'list') {
            return [];
        }

        $embedData = (array) ($this->responseData['data'] ?? []);

        // if (count($embeddingsData) === 0) {
        //     throw new \Exception('No embeddings data found in response'); // todo - throw a custom exception
        // }

        $embeddings = [];
        foreach ($embedData as $currentEmbedData) {
            $embeddings[] = $this->buildEmbeddingVector($currentEmbedData);
        }

        return $embeddings;
    }

    /**
     * Build a message from message data.
     *
     * @param array<string,mixed> $embedData The data to build the message from.
     * @return Vector|null
     */
    private function buildEmbeddingVector(array $embedData): ?Vector
    {
        if ($embedData['object'] !== 'embedding') {
            return null;
        }

        /** @var array<float>|null $vertices */
        $vertices = $embedData['embedding'] ?? null;

        return \is_array($vertices) && \count($vertices) > 0
            ? new Vector($vertices)
            : null;
    }
}
