<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Registry;

use BackedEnum;
use DigitalAnomaly\AlteredLogic\Common\Enums\FrameworksEnum;
use DigitalAnomaly\AlteredLogic\Exceptions\RegistryException;
use DigitalAnomaly\AlteredLogic\Interfaces\HasRegisteredNameInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Registry\HasDefaultNameInterface;
use DigitalAnomaly\AlteredLogic\Support\Framework\CapabilityDetector;

/**
 * Contains a group of registered entities.
 *
 * One can be marked as being the default.
 *
 * @template T of object
 */
abstract class AbstractRegistryGroup
{
    /** @var array<string,T> The entities in the group. */
    private array $entities = [];

    /** @var string The default entity's name. */
    private string $default = '';

    /** @var string The name of the group. */
    protected string $registryName;



    /**
     * Register an entity.
     *
     * @param string|BackedEnum $name      The entity's name, for identifying it later.
     * @param T                 $entity    The entity to register.
     * @param boolean           $isDefault Whether this is the default entity or not.
     * @return void
     * @throws RegistryException If the name is invalid.
     */
    public function register( // @phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
        string|BackedEnum $name,
        object $entity,
        bool $isDefault = false,
    ): void {

        $fallbackName = $entity instanceof HasDefaultNameInterface
            ? $entity->getDefaultName()
            : '';

        $name = self::resolveName(
            $name,
            $fallbackName,
            false,
            false,
            false,
        );

        // don't allow the same name to be registered twice
        if (\array_key_exists($name, $this->entities)) {
            throw RegistryException::entityAlreadyRegistered($this->registryName, $name);
        }

        $this->entities[$name] = $entity;

        if ($this->default === '' || $isDefault) {
            $this->default = $name;
        }

        // let the entity know what name it's registered under
        if ($entity instanceof HasRegisteredNameInterface) {
            $entity->setRegisteredName($name);
        }
    }



    /**
     * Get an entity by name. If no name is provided, the default is returned.
     *
     * @param string|BackedEnum|null $name          The name of the entity to get.
     * @param boolean                $allowNotFound Throw an exception if the specified name is not found.
     * @param boolean                $allowEmpty    Throw an exception if the name is empty.
     * @return T|null
     * @throws RegistryException If the name is invalid or the entity cannot be resolved, provided that's not allowed.
     */
    public function get(
        string|BackedEnum|null $name = null,
        bool $allowNotFound = false,
        bool $allowEmpty = false,
    ): ?object {

        $resolvedName = $this->resolveNameForGet($name);

        // if the name couldn't be resolved
        if ($resolvedName === '') {

            if ($allowEmpty) {
                return null;
            }

            \count($this->entities) > 0
                ? throw RegistryException::invalidName($this->registryName, $resolvedName)
                : throw RegistryException::noEntriesRegistered($this->registryName);
        }

        // if it hasn't been registered yet, check to see if the framework can build it
        if (!\array_key_exists($resolvedName, $this->entities)) {

            $framework = self::pickFramework();
            if ($framework !== null) {

                $entity = static::frameworkBuildEntity($framework, $resolvedName);
                if ($entity !== null) {

                    // register manually instead of calling register()
                    // - so as to avoid setting the default if it's the first entity built, but is not supposed to be
                    //   the default
                    $this->entities[$resolvedName] = $entity;

                    $defaultName = static::frameworkResolveDefaultEntityName($framework, true);
                    $defaultName = self::normaliseNameString($defaultName);

                    $isDefault = $defaultName !== '' && $resolvedName === $defaultName;
                    if ($isDefault) {
                        $this->default = $resolvedName;
                    }

                    // let the entity know what name it's registered under
                    if ($entity instanceof HasRegisteredNameInterface) {
                        $entity->setRegisteredName($resolvedName);
                    }
                }
            }
        }

        if (\array_key_exists($resolvedName, $this->entities)) {
            return $this->entities[$resolvedName];
        }

        return $allowNotFound
            ? null
            : throw RegistryException::entityNotRegistered($this->registryName, $resolvedName);
    }

    /**
     * Get an entity by name. If no name is provided, the default is returned.
     *
     * An exception is thrown if the name is invalid or the entity cannot be resolved.
     *
     * @param string|BackedEnum $name The name of the entity to get.
     * @return T
     * @throws RegistryException If the name is invalid or the entity cannot be resolved.
     */
    public function getOrThrow(string|BackedEnum $name): object
    {
        $return = self::get($name, false, false);

        \assert(\is_object($return));

        return $return;
    }



    /**
     * Get the name of an entity. If no name is provided, the default is returned (this is useful when trying to resolve
     * the name of the default entity).
     *
     * @param string|BackedEnum|null $name          The name of the entity to get.
     * @param boolean                $allowNotFound Throw an exception if the specified name is not found.
     * @param boolean                $allowEmpty    Throw an exception if the name is empty.
     * @return string|null
     * @throws RegistryException If the name is invalid or the entity cannot be resolved, provided that's not allowed.
     */
    public function getName(
        string|BackedEnum|null $name = null,
        bool $allowNotFound = false,
        bool $allowEmpty = false,
    ): string|null {

        $name = $this->resolveNameForGet($name);

        // check if the entity can be resolved
        if (self::get($name, $allowNotFound, $allowEmpty) === null) {
            return null;
        }

        return $name;
    }

    /**
     * Get the name of an entity. If no name is provided, the default is returned (this is useful when trying to resolve
     * the name of the default entity).
     *
     * An exception is thrown if the name is invalid or the entity cannot be resolved.
     *
     * @param string|BackedEnum $name The name of the entity to get.
     * @return string
     * @throws RegistryException If the name is invalid or the entity cannot be resolved.
     */
    public function getNameOrThrow(string|BackedEnum $name): string
    {
        $return = self::getName($name, false, false);

        \assert(\is_string($return));

        return $return;
    }



    /**
     * Resolve the name of an entity, for the get() method.
     *
     * @param string|BackedEnum|null $name The name of the entity to resolve.
     * @return string
     */
    private function resolveNameForGet(string|BackedEnum|null $name): string
    {
        return self::resolveName(
            $name,
            null,
            true,
            true,
            false,
        );
    }

    /**
     * Helper method to resolve a registered name to a string.
     *
     * @param string|BackedEnum|null $name                          The name to resolve.
     * @param string|BackedEnum|null $fallbackName                  The entity's fallback name.
     * @param boolean                $considerFrameworkDefaultName  Whether to get the default name from the framework
     *                                                              or not.
     * @param boolean                $considerRegisteredDefaultName Whether to allow the registered default name to be
     *                                                              used or not.
     * @param boolean                $throw                         Whether to throw an exception if the name is invalid
     *                                                              or not.
     * @return string
     * @throws RegistryException If the name is invalid and $throw is true.
     */
    private function resolveName(
        string|BackedEnum|null $name,
        string|BackedEnum|null $fallbackName = null,
        bool $considerFrameworkDefaultName = true,
        bool $considerRegisteredDefaultName = true,
        bool $throw = false,
    ): string {

        $name = self::normaliseNameString($name);
        if ($name !== '') {
            return $name;
        }

        $name = self::normaliseNameString($fallbackName);
        if ($name !== '') {
            return $name;
        }

        if ($considerFrameworkDefaultName) {

            $framework = self::pickFramework();
            if ($framework !== null) {

                $defaultName = static::frameworkResolveDefaultEntityName($framework, true);
                $defaultName = self::normaliseNameString($defaultName);
                if ($defaultName !== '') {
                    return $defaultName;
                }
            }
        }

        if ($considerRegisteredDefaultName) {
            if ($this->default !== '') {
                return $this->default;
            }
        }

        if (!$throw) {
            return '';
        }

        return throw RegistryException::invalidName($this->registryName, $name);
    }

    /**
     * Resolve a name to a string.
     *
     * @param string|BackedEnum|null $name The name to resolve.
     * @return string
     */
    private static function normaliseNameString(string|BackedEnum|null $name): string
    {
        return $name instanceof BackedEnum
            ? (string) $name->value
            : $name ?? '';
    }





    /**
     * Pick the first available framework.
     *
     * In case the user needs to be able to choose the order of preference later.
     *
     * @return FrameworksEnum|null
     */
    public static function pickFramework(): ?FrameworksEnum
    {
        // todo - add other frameworks
        $frameworks = [
            FrameworksEnum::Laravel,
        ];

        /** @var FrameworksEnum|null $return */
        $return = CapabilityDetector::pickFunctionalityToUse($frameworks);

        return $return;
    }





    /**
     * Get the name of the "default" entity, specified in the framework's config.
     *
     * @return string|null
     */
    public static function frameworkGetDefaultEntityName(): ?string
    {
        $framework = self::pickFramework();
        if ($framework === null) {
            return null;
        }

        $defaultName = static::frameworkResolveDefaultEntityName($framework, false);
        $defaultName = self::normaliseNameString($defaultName);
        if ($defaultName === '') {
            return null;
        }

        return $defaultName;
    }



    /**
     * Resolve the name of the default entity using the framework and its configuration.
     *
     * @param FrameworksEnum $framework   The framework to get the name from.
     * @param boolean        $checkExists Whether to check if the entity has been defined in the configuration when set.
     * @return string|BackedEnum|null
     */
    abstract protected static function frameworkResolveDefaultEntityName(
        FrameworksEnum $framework,
        bool $checkExists,
    ): string|BackedEnum|null;

    /**
     * Build an entity using the framework and its configuration.
     *
     * @param FrameworksEnum $framework The framework to build the entity for.
     * @param string         $name      The name of the entity to build.
     * @return T|null
     */
    abstract protected static function frameworkBuildEntity(FrameworksEnum $framework, string $name): ?object;
}
