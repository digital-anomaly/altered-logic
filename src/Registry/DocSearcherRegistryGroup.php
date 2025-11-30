<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Registry;

use BackedEnum;
use DigitalAnomaly\AlteredLogic\Common\Enums\FrameworksEnum;
use DigitalAnomaly\AlteredLogic\Frameworks\Laravel\LaravelFrameworkRegistryBuilder;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocSearcherInterface;

/**
 * Registry group for DocSearcher instances.
 *
 * @extends AbstractRegistryGroup<DocSearcherInterface>
 */
class DocSearcherRegistryGroup extends AbstractRegistryGroup
{
    /** @var string The name of the group. */
    protected string $registryName = 'doc-searcher';



    /**
     * Resolve the name of the default entity using the framework and its configuration.
     *
     * @param FrameworksEnum $framework   The framework to get the name from.
     * @param boolean        $checkExists Whether to check if the entity has been defined in the configuration when set.
     * @return string|BackedEnum|null
     */
    protected static function frameworkResolveDefaultEntityName(
        FrameworksEnum $framework,
        bool $checkExists,
    ): string|BackedEnum|null {

        // Doc-searchers don't have defaults - they're only accessible via DocumentProfiles
        return null;
    }

    /**
     * Build an entity using the framework and its configuration.
     *
     * @param FrameworksEnum $framework The framework to build the entity for.
     * @param string         $name      The name of the entity to build.
     * @return DocSearcherInterface|null
     */
    protected static function frameworkBuildEntity(FrameworksEnum $framework, string $name): ?DocSearcherInterface
    {
        // todo - add other frameworks
        return match ($framework) {
            FrameworksEnum::Laravel => LaravelFrameworkRegistryBuilder::buildDocSearcher($name),
            default => null,
        };
    }
}
