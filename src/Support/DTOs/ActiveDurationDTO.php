<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\DTOs;

/**
 * Class to record a duration of time.
 */
final readonly class ActiveDurationDTO
{
    /**
     * Constructor - use new() instead.
     *
     * @param integer|float|null $startTimestamp The start time.
     * @param integer|float|null $endTimestamp   The end time.
     * @param integer|float|null $activeSeconds  How long it was active for (defaults to overall duration when null).
     * @param integer|float|null $overallSeconds The real duration of time.
     */
    private function __construct(
        public int|float|null $startTimestamp = null,
        public int|float|null $endTimestamp = null,
        public int|float|null $activeSeconds = null,
        public int|float|null $overallSeconds = null,
    ) {}



    /**
     * Alternative constructor.
     *
     * @param integer|float|null $startTimestamp The start time.
     * @param integer|float|null $endTimestamp   The end time.
     * @param integer|float|null $activeSeconds  How long it was active for (defaults to overall duration when null).
     * @return self
     */
    public static function new(
        int|float|null $startTimestamp = null,
        int|float|null $endTimestamp = null,
        int|float|null $activeSeconds = null,
    ): self {

        $overallSeconds = $startTimestamp !== null && $endTimestamp !== null
            ? $endTimestamp - $startTimestamp
            : null;

        if ($activeSeconds === null) {
            $activeSeconds = $overallSeconds;
        }

        return new self(
            $startTimestamp,
            $endTimestamp,
            $activeSeconds,
            $overallSeconds,
        );
    }



    /**
     * Build a new DurationDTO that's a combination of the given DurationDTOs.
     *
     * @param array<self|null> $durationDTOs The DurationDTOs to combine.
     * @return self|null
     */
    public static function combine(array $durationDTOs): ?self
    {
        // remove nulls
        $durationDTOs = \array_filter($durationDTOs, fn($value) => $value !== null);

        if (\count($durationDTOs) === 0) {
            return null;
        }

        $startTimestamps = $endTimestamps = $activeSeconds = [];
        foreach ($durationDTOs as $durationDTO) {

            if ($durationDTO->startTimestamp !== null) {
                $startTimestamps[] = $durationDTO->startTimestamp;
            }

            if ($durationDTO->endTimestamp !== null) {
                $endTimestamps[] = $durationDTO->endTimestamp;
            }

            if ($durationDTO->activeSeconds !== null) {
                $activeSeconds[] = $durationDTO->activeSeconds;
            }
        }

        $startTimestamp = \count($startTimestamps) > 0
            ? \min($startTimestamps)
            : null;

        $endTimestamp = \count($endTimestamps) > 0
            ? \max($endTimestamps)
            : null;

        $activeSeconds = \count($activeSeconds) > 0
            ? \array_sum($activeSeconds)
            : null;

        if ($startTimestamp === null && $endTimestamp === null && $activeSeconds === null) {
            return null;
        }

        return self::new(
            $startTimestamp,
            $endTimestamp,
            $activeSeconds,
        );
    }
}
