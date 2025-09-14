<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Http\DTOs;

/**
 * Class to record a duration of time.
 */
final readonly class DurationDTO
{
    /**
     * Constructor - use new() instead.
     *
     * @param integer|float|null $startTimestamp  The start time.
     * @param integer|float|null $endTimestamp    The end time.
     * @param integer|float|null $durationSeconds The duration of time.
     */
    private function __construct(
        public int|float|null $startTimestamp = null,
        public int|float|null $endTimestamp = null,
        public int|float|null $durationSeconds = null,
    ) {}



    /**
     * Alternative constructor.
     *
     * @param integer|float|null $startTimestamp The start time (in seconds since epoch).
     * @param integer|float|null $endTimestamp   The end time (in seconds since epoch).
     * @return self
     */
    public static function new(
        int|float|null $startTimestamp = null,
        int|float|null $endTimestamp = null,
    ): self {

        $durationSeconds = $startTimestamp !== null && $endTimestamp !== null
            ? $endTimestamp - $startTimestamp
            : null;

        return new self(
            $startTimestamp,
            $endTimestamp,
            $durationSeconds,
        );
    }
}
