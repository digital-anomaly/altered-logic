<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\DTOs;

/**
 * Representation of the number of tokens used in a multimodal transmission.
 */
final readonly class ModexTokenUsageDTO
{
    /** @var integer The total number of tokens used. */
    public int $totalTokens;

    /** @var integer The number of input (prompt) tokens used. */
    public int $inputTokens;

    /** @var integer The number of output (completion) tokens used. */
    public int $outputTokens;

    /** @var integer Out of the overall input tokens, the number that were cached. */
    public int $cachedInputTokens;

    /** @var integer Out of the overall output tokens, the number that were cached. */
    public int $cachedOutputTokens;



    /**
     * Constructor.
     *
     * @param integer $inputTokens        The number of input (prompt) tokens used.
     * @param integer $outputTokens       The number of output (completion) tokens used.
     * @param integer $cachedInputTokens  Out of the overall input tokens, the number that were retrieved from cache.
     * @param integer $cachedOutputTokens Out of the overall output tokens, the number that were retrieved from cache.
     */
    public function __construct(
        int $inputTokens,
        int $outputTokens,
        int $cachedInputTokens,
        int $cachedOutputTokens,
    ) {
        $this->totalTokens = $inputTokens + $outputTokens;
        $this->inputTokens = $inputTokens;
        $this->outputTokens = $outputTokens;
        $this->cachedInputTokens = $cachedInputTokens;
        $this->cachedOutputTokens = $cachedOutputTokens;
    }



    /**
     * Build a new TokenUsageDTO that's a combination of the given TokenUsageDTOs.
     *
     * @param array<self|null> $tokenUsageDTOs The TokenUsageDTO to combine.
     * @return self
     */
    public static function combine(array $tokenUsageDTOs): self
    {
        // todo - is it necessary to accept (and filter out) nulls? will this situation happen
        $tokenUsageDTOs = \array_filter($tokenUsageDTOs, fn($value) => $value !== null);

        $inputTokens = 0;
        $outputTokens = 0;
        $cachedInputTokens = 0;
        $cachedOutputTokens = 0;

        foreach ($tokenUsageDTOs as $tokenUsageDTO) {
            $inputTokens += $tokenUsageDTO->inputTokens;
            $outputTokens += $tokenUsageDTO->outputTokens;
            $cachedInputTokens += $tokenUsageDTO->cachedInputTokens;
            $cachedOutputTokens += $tokenUsageDTO->cachedOutputTokens;
        }

        return new self($inputTokens, $outputTokens, $cachedInputTokens, $cachedOutputTokens);
    }
}
