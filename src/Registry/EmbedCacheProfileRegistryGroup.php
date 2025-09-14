<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Registry;

use BackedEnum;
use DigitalAnomaly\AlteredLogic\Common\Enums\FrameworksEnum;
use DigitalAnomaly\AlteredLogic\Frameworks\Laravel\LaravelFrameworkRegistryBuilder;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedCacheProfile;

/**
 * Contains a group of registered embed cache profiles.
 *
 * @extends AbstractRegistryGroup<EmbedCacheProfile>
 */
class EmbedCacheProfileRegistryGroup extends AbstractRegistryGroup
{
    /** @var string The name of the group. */
    protected string $registryName = 'embed-cache-profile';



    /**
     * Resolve the name of the default entity using the framework and its configuration.
     *
     * @param FrameworksEnum $framework The framework to get the name from.
     * @return string|BackedEnum|null
     */
    protected function frameworkResolveDefaultEntityName(FrameworksEnum $framework): string|BackedEnum|null
    {
        // todo - add other frameworks
        return match ($framework) {
            FrameworksEnum::Laravel => LaravelFrameworkRegistryBuilder::getDefaultEmbedCacheProfileName(),
            default => null,
        };
    }

    /**
     * Build an entity using the framework and its configuration.
     *
     * @param FrameworksEnum $framework The framework to build the entity for.
     * @param string         $name      The name of the entity to build.
     * @return EmbedCacheProfile|null
     */
    protected function frameworkBuildEntity(FrameworksEnum $framework, string $name): ?EmbedCacheProfile
    {
        // todo - add other frameworks
        return match ($framework) {
            FrameworksEnum::Laravel => LaravelFrameworkRegistryBuilder::buildEmbedCacheProfile($name),
            default => null,
        };
    }
}
