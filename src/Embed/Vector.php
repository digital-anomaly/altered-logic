<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed;

/**
 * Class to represent a vector.
 */
final readonly class Vector
{
    /**
     * Constructor.
     *
     * @param array<float> $coordinates The vector's coordinates.
     */
    public function __construct(
        private array $coordinates,
    ) {}



    /**
     * Get the Vector's coordinates.
     *
     * @return array<float> The coordinates.
     */
    public function coordinates(): array
    {
        return \array_values($this->coordinates);
    }

    /**
     * Get the number of dimensions in the Vector.
     *
     * @return integer The number of dimensions.
     */
    public function dimensions(): int
    {
        return \count($this->coordinates);
    }

    /**
     * Get the Vector as a PHP code string.
     *
     * @return string The PHP code string.
     */
    public function toPhp(): string
    {
        return 'new Vector(' . \json_encode($this->coordinates) . ')';
    }
}
