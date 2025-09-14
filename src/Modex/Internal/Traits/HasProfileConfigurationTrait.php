<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal\Traits;

use DigitalAnomaly\AlteredLogic\Exceptions\RegistryException;
use DigitalAnomaly\AlteredLogic\Profiles\ModexModelProfile;
use DigitalAnomaly\AlteredLogic\Registry\Registry;

/**
 * Trait that provides methods that manage the model profile to use when interacting with an AI provider.
 */
trait HasProfileConfigurationTrait
{
    /** @var string|null The model profile to use (will use the default one if not specified). */
    public ?string $modelProfileName = null;



    /**
     * Specify the model profile to use when making requests.
     *
     * @param string $modelProfileName The name of the model profile to use.
     * @return self
     */
    public function modelProfile(string $modelProfileName): self
    {
        $this->modelProfileName = $modelProfileName !== ''
            ? $modelProfileName
            : null;

        return $this;
    }

    /**
     * Resolve the modex model profile to use.
     *
     * @return ModexModelProfile
     * @throws RegistryException If the modex model profile is not found.
     */
    private function resolveModelProfile(): ModexModelProfile
    {
        return Registry::modexModelProfiles()->getOrThrow((string) $this->modelProfileName);
    }
}
