<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal\Traits;

use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
use DigitalAnomaly\AlteredLogic\Credentials\CredentialsOverride;
use DigitalAnomaly\AlteredLogic\Exceptions\ModexException;
use DigitalAnomaly\AlteredLogic\Exceptions\RegistryException;
use DigitalAnomaly\AlteredLogic\Profiles\ModexModelProfile;
use DigitalAnomaly\AlteredLogic\Registry\Registry;

/**
 * Trait that provides methods that manage the model profile to use when interacting with an AI provider.
 */
trait HasProfileConfigurationTrait
{
    /** @var string|null The model profile to use. */
    public ?string $modelProfileName = null;

    /** @var string|null The model to use directly (instead of using a model profile). */
    public ?string $modelName = null;

    /** @var CredentialsOverride|null The credentials override to use (instead of each model's own credentials). */
    public ?CredentialsOverride $credentialsOverride = null;



    /**
     * Specify the model profile to use when making requests.
     *
     * @param string|null $modelProfileName The name of the model profile to use.
     * @return self
     */
    public function modelProfile(?string $modelProfileName): self
    {
        $this->modelProfileName = $modelProfileName !== ''
            ? $modelProfileName
            : null;

        $this->modelName = null; // override the model

        return $this;
    }

    /**
     * Specify the model to use directly (instead of using a model profile).
     *
     * @param string|null $modelName The name of the model to use.
     * @return self
     */
    public function model(?string $modelName): self
    {
        $this->modelProfileName = null; // override the model profile

        $this->modelName = $modelName !== ''
            ? $modelName
            : null;

        return $this;
    }

    /**
     * Specify the credentials to use, overriding each model's configured credentials.
     *
     * Composes with model()/modelProfile() - it clears neither. Pass null to clear the override.
     *
     * @param CredentialsOverride|string|AiProvidersEnum|array<string,string|AiProvidersEnum>|null $credentials A
     *        credentials name to use for all providers, or a map of provider name => credentials name. Map values are
     *        registered credentials names, but map keys are matched against each model's getProvider() value (they're
     *        not looked up anywhere) - an unrecognised key is ignored silently.
     * @return self
     */
    public function credentials(CredentialsOverride|string|AiProvidersEnum|array|null $credentials): self
    {
        $this->credentialsOverride = CredentialsOverride::from($credentials);

        return $this;
    }



    /**
     * Resolve the modex model profile to use.
     *
     * @return ModexModelProfile
     * @throws ModexException If no profile or model could be resolved.
     */
    private function resolveModelProfile(): ModexModelProfile
    {
        // 1. Check for explicit profile name
        if ($this->modelProfileName !== null) {
            try {
                return Registry::modexModelProfiles()->getOrThrow($this->modelProfileName);
            } catch (RegistryException $e) {
                throw ModexException::modexModelProfileNotFound($this->modelProfileName, $e);
            }
        }

        // 2. Check for explicit model name
        if ($this->modelName !== null) {
            try {
                return Registry::modexModels()->getOrThrow($this->modelName)->getModelProfile();
            } catch (RegistryException $e) {
                throw ModexException::modexModelNotFound($this->modelName, $e);
            }
        }

        // 3. Try the default profile
        $modelProfile = Registry::modexModelProfiles()->get(allowNotFound: true, allowEmpty: true);
        if ($modelProfile !== null) {
            return $modelProfile;
        }

        // 4. Try the default model
        $modelProfile = Registry::modexModels()->get(allowNotFound: true, allowEmpty: true)?->getModelProfile();
        if ($modelProfile !== null) {
            return $modelProfile;
        }

        // throw an exception if a default model profile has been specified but not found
        $defaultName = Registry::modexModelProfiles()::frameworkGetDefaultEntityName();
        if ($defaultName !== null) {
            throw ModexException::modexModelProfileNotFound($defaultName);
        }

        // 5. Nothing found
        throw ModexException::noModexModelOrProfileConfigured();
    }
}
