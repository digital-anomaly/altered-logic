<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Exceptions;

/**
 * Exceptions related to Registries.
 */
final class RegistryException extends AlteredLogicException
{
    /**
     * Thrown when no entries have been registered.
     *
     * @param string $registryName The name of the registry.
     * @return self
     */
    public static function noEntriesRegistered(string $registryName): self
    {
        return new self("No entries have been registered for the {$registryName} registry");
    }

    /**
     * Thrown when a name is invalid.
     *
     * @param string $registryName The name of the registry.
     * @param string $entityName   The name that was invalid.
     * @return self
     */
    public static function invalidName(string $registryName, string $entityName): self
    {
        return new self("The {$registryName} name '{$entityName}' is invalid");
    }

    /**
     * Thrown when an entity has already been registered.
     *
     * @param string $registryName The name of the registry.
     * @param string $entityName   The name of the entity.
     * @return self
     */
    public static function entityAlreadyRegistered(string $registryName, string $entityName): self
    {
        return new self("The {$registryName} '{$entityName}' has already been registered");
    }

    /**
     * Thrown when an entity has not been registered.
     *
     * @param string $registryName The name of the registry.
     * @param string $entityName   The name of the entity.
     * @return self
     */
    public static function entityNotRegistered(string $registryName, string $entityName): self
    {
        return new self("The {$registryName} '{$entityName}' has not been configured / registered");
    }
}
