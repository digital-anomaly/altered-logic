<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Interfaces\Registry;

use BackedEnum;

/**
 * Interface for a registry entry that has a default name.
 */
interface HasDefaultNameInterface
{
    /**
     * Get the default name.
     *
     * @return string|BackedEnum
     */
    public function getDefaultName(): string|BackedEnum;
}
