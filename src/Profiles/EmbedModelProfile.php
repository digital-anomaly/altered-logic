<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Profiles;

use DigitalAnomaly\AlteredLogic\AlteredLogic;
use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
use DigitalAnomaly\AlteredLogic\Credentials\CredentialsOverride;
use DigitalAnomaly\AlteredLogic\Exceptions\CredentialsException;
use DigitalAnomaly\AlteredLogic\Exceptions\EmbedException;
use DigitalAnomaly\AlteredLogic\Exceptions\RegistryException;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedApiClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedModelInterface;
use DigitalAnomaly\AlteredLogic\Registry\Registry;
use DigitalAnomaly\AlteredLogic\Support\Framework\DependencyInjection;

/**
 * A profile containing a priority list of embed models to use.
 */
final class EmbedModelProfile
{
    /** @var string[] The model names to use in order of preference. */
    private array $modelPreferences = [];



    /**
     * Add a model to the preference list.
     *
     * @param string $registeredModelName The name of the model to add.
     * @return self
     */
    public function addModel(string $registeredModelName): self
    {
        $this->modelPreferences[] = $registeredModelName;

        return $this;
    }

    /**
     * Add multiple models to the preference list.
     *
     * @param string[] $registeredModelNames The names of the models to add.
     * @return self
     */
    public function addModels(array $registeredModelNames): self
    {
        foreach ($registeredModelNames as $registeredModelName) {
            $this->addModel($registeredModelName);
        }

        return $this;
    }





    /**
     * Get the dimensions from the first available model in the profile.
     *
     * @return integer
     * @throws EmbedException If no valid model could be found.
     */
    public function getDimensions(): int
    {
        foreach ($this->modelPreferences as $modelName) {

            // resolve the model from the registry
            $modelPreference = Registry::embedModels()->get($modelName, allowNotFound: true);
            if ($modelPreference === null) {
                continue; // skip if model not registered
            }

            return $modelPreference->getDimensions();
        }

        throw EmbedException::embedApiClientCouldNotBeResolved();
    }



    /**
     * Resolve and build the Embed API client.
     *
     * @param CredentialsOverride|null $credentialsOverride The credentials to use instead of each model's own.
     * @return array{apiClient:EmbedApiClientInterface,embedModel:EmbedModelInterface}
     * @throws EmbedException If the embed API client could not be resolved.
     * @throws CredentialsException If a matched override's credentials name isn't registered.
     */
    public function buildEmbedApiClient(?CredentialsOverride $credentialsOverride = null): array
    {
        foreach ($this->modelPreferences as $modelName) {

            // resolve the model from the registry
            $modelPreference = Registry::embedModels()->get($modelName, allowNotFound: true);
            if ($modelPreference === null) {
                continue; // skip if model not registered
            }

            // resolve the credentials from the registry - a matched override must resolve or throw (never fall back),
            // otherwise fall back to the model's own credentials (skipping the preference if they're not found)
            $overrideName = $credentialsOverride?->pickCredentialsName($modelPreference->getProvider());
            if ($overrideName !== null) {
                try {
                    $credentials = Registry::credentials()->getOrThrow($overrideName);
                } catch (RegistryException $e) {
                    $provider = $modelPreference->getProvider();
                    throw CredentialsException::overrideCredentialsNotFound(
                        $overrideName,
                        $modelName,
                        $provider instanceof AiProvidersEnum ? $provider->value : $provider,
                        $credentialsOverride->isUniversal(),
                        $e,
                    );
                }
            } else {
                $credentials = Registry::credentials()->get($modelPreference->getCredentials(), true);
                if ($credentials === null) {
                    continue; // skip if credentials not found
                }
            }

            $clientClass = $modelPreference->getClientClass();

            if (!\is_subclass_of($clientClass, EmbedApiClientInterface::class)) {
                throw EmbedException::invalidEmbedApiClient($clientClass);
            }

            try {

                /** @var EmbedApiClientInterface $embedApiClient */
                $embedApiClient = DependencyInjection::instantiate(
                    $clientClass,
                    [],
                    [
                        'embedModel' => $modelPreference,
                        'credentials' => $credentials,
                    ]
                );

            } catch (\Throwable $e) {
                throw EmbedException::embedApiClientCouldNotBeResolved(previous: $e);
            }

            return [
                'apiClient' => $embedApiClient,
                'embedModel' => $modelPreference,
            ];
        }

        throw EmbedException::embedApiClientCouldNotBeResolved();
    }



    /**
     * Register this embed model profile.
     *
     * @param string  $name      The name of the profile to register.
     * @param boolean $isDefault Whether this is the default profile or not (the first one is default unless another is
     *                           specified).
     * @return void
     */
    public function register(string $name, bool $isDefault = false): void
    {
        AlteredLogic::registerEmbedModelProfile($name, $this, $isDefault);
    }
}
