<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Registry;

use DigitalAnomaly\AlteredLogic\Interfaces\HasRegisteredNameInterface;

/**
 * Trait for a registry entity that has a registered name.
 *
 * @see HasRegisteredNameInterface
 */
trait HasRegisteredNameTrait
{
    /** @var string|null The registered name of this entity. */
    private ?string $registeredName = null;



    /**
     * Check if this entity is registered.
     *
     * @return boolean
     */
    private function isRegistered(): bool
    {
        return $this->registeredName !== null;
    }

    /**
     * Get the registered name of this entity.
     *
     * @return string|null
     */
    private function getRegisteredName(): ?string
    {
        return $this->registeredName;
    }

    /**
     * Set the name this entity is registered under.
     *
     * @param string $registeredName The name this entity is registered under.
     * @return void
     */
    public function setRegisteredName(string $registeredName): void
    {
        $this->registeredName = $registeredName;
    }
}
