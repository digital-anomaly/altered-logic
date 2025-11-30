<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Interfaces;

use DigitalAnomaly\AlteredLogic\Registry\HasRegisteredNameTrait;

/**
 * Interface for a registry entity that has a registered name.
 *
 * @see HasRegisteredNameTrait
 */
interface HasRegisteredNameInterface
{
    /**
     * Set the name this entity is registered under.
     *
     * @param string $registeredName The name this entity is registered under.
     * @return void
     */
    public function setRegisteredName(string $registeredName): void;
}
