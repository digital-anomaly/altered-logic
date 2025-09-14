<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Modex\Transformers;

use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTokenUsageDTO;

/**
 * Extracts the token usage from the response.
 */
final class OpenAiResponsesApiInboundResponseTokenUsageTransformer
{
    /**
     * Build the token usage from the response.
     *
     * @param array<string,mixed> $responseData The response from the AI provider.
     * @return ModexTokenUsageDTO
     */
    public static function buildTokenUsage(array $responseData): ModexTokenUsageDTO
    {
        // "usage" => array:5 [
        //   "input_tokens" => 24
        //   "input_tokens_details" => array:1 [
        //     "cached_tokens" => 0
        //   ]
        //   "output_tokens" => 8
        //   "output_tokens_details" => array:1 [
        //     "reasoning_tokens" => 0
        //   ]
        //   "total_tokens" => 32
        // ]

        $tokenUsageData = (array) ($responseData['usage'] ?? []);

        // return new MultimodalTokenUsageDTO(
        //     100, 200, 10, 0
        // );

        return new ModexTokenUsageDTO(
            (int) ($tokenUsageData['input_tokens'] ?? 0),
            (int) ($tokenUsageData['output_tokens'] ?? 0),
            (int) ($tokenUsageData['input_tokens_details']['cached_tokens'] ?? 0),
            (int) ($tokenUsageData['output_tokens_details']['reasoning_tokens'] ?? 0),
        );
    }
}
