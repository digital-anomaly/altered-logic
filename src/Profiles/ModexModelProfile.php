<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Profiles;

use DigitalAnomaly\AlteredLogic\AlteredLogic;
use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
use DigitalAnomaly\AlteredLogic\Credentials\CredentialsOverride;
use DigitalAnomaly\AlteredLogic\Exceptions\CredentialsException;
use DigitalAnomaly\AlteredLogic\Exceptions\ModexException;
use DigitalAnomaly\AlteredLogic\Exceptions\RegistryException;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\ModexApiClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\ModexModelInterface;
use DigitalAnomaly\AlteredLogic\Registry\Registry;
use DigitalAnomaly\AlteredLogic\Support\Framework\DependencyInjection;

/**
 * A model profile containing a priority list of modex models to use.
 */
final class ModexModelProfile
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
     * Register this modex model profile.
     *
     * @param string  $name      The name of the profile to register.
     * @param boolean $isDefault Whether this is the default profile or not (the first one is default unless another is
     *                           specified).
     * @return void
     */
    public function register(string $name, bool $isDefault = false): void
    {
        AlteredLogic::registerModexModelProfile($name, $this, $isDefault);
    }





    /**
     * Resolve and build the Modex API client.
     *
     * @param CredentialsOverride|null $credentialsOverride The credentials to use instead of each model's own.
     * @return array{apiClient:ModexApiClientInterface,modexModel:ModexModelInterface}
     * @throws ModexException If the Modex API client could not be resolved.
     * @throws CredentialsException If a matched override's credentials name isn't registered.
     */
    public function buildModexApiClient(?CredentialsOverride $credentialsOverride = null): array
    {
        foreach ($this->modelPreferences as $modelName) {

            // resolve the model from the registry
            $modelPreference = Registry::modexModels()->get($modelName, allowNotFound: true);
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

            if (!\is_subclass_of($clientClass, ModexApiClientInterface::class)) {
                throw ModexException::invalidModexApiClient($clientClass);
            }

            try {

                /** @var ModexApiClientInterface $modexApiClient */
                $modexApiClient = DependencyInjection::instantiate(
                    $clientClass,
                    [],
                    [
                        'modexModel' => $modelPreference,
                        'credentials' => $credentials,
                    ],
                );

            } catch (\Throwable $e) {
                throw ModexException::modexApiClientCouldNotBeResolved(previous: $e);
            }

            return [
                'apiClient' => $modexApiClient,
                'modexModel' => $modelPreference,
            ];
        }

        throw ModexException::modexApiClientCouldNotBeResolved();
    }
}
