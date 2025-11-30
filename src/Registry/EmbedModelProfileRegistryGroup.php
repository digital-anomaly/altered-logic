<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Registry;

use BackedEnum;
use DigitalAnomaly\AlteredLogic\Common\Enums\FrameworksEnum;
use DigitalAnomaly\AlteredLogic\Frameworks\Laravel\LaravelFrameworkRegistryBuilder;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedModelProfile;

/**
 * Contains a group of registered embed model profiles.
 *
 * @extends AbstractRegistryGroup<EmbedModelProfile>
 */
class EmbedModelProfileRegistryGroup extends AbstractRegistryGroup
{
    /** @var string The name of the group. */
    protected string $registryName = 'embed-model-profile';



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

        // todo - add other frameworks
        return match ($framework) {
            FrameworksEnum::Laravel => LaravelFrameworkRegistryBuilder::getDefaultEmbedModelProfileName($checkExists),
            default => null,
        };
    }

    /**
     * Build an entity using the framework and its configuration.
     *
     * @param FrameworksEnum $framework The framework to build the entity for.
     * @param string         $name      The name of the entity to build.
     * @return EmbedModelProfile|null
     */
    protected static function frameworkBuildEntity(FrameworksEnum $framework, string $name): ?EmbedModelProfile
    {
        // todo - add other frameworks
        return match ($framework) {
            FrameworksEnum::Laravel => LaravelFrameworkRegistryBuilder::buildEmbedModelProfile($name),
            default => null,
        };
    }
}
