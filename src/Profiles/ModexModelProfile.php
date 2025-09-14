<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Profiles;

use DigitalAnomaly\AlteredLogic\AlteredLogic;
use DigitalAnomaly\AlteredLogic\Exceptions\ModexException;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\ModexApiClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\ModexModelInterface;
use DigitalAnomaly\AlteredLogic\Registry\Registry;
use DigitalAnomaly\AlteredLogic\Support\Framework\DependencyInjection;

/**
 * A model profile containing a priority list of modex models to use.
 */
final class ModexModelProfile
{
    /** @var ModexModelInterface[] The models to use in order of preference. */
    private array $modelPreferences = [];



    /**
     * Add a model to the preference list.
     *
     * @param ModexModelInterface $model The model to add.
     * @return self
     */
    public function addModel(ModexModelInterface $model): self
    {
        $this->modelPreferences[] = $model;

        return $this;
    }



    /**
     * Register the modex model.
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
     * @return array{apiClient:ModexApiClientInterface,modexModel:ModexModelInterface}
     * @throws ModexException If the Modex API client could not be resolved.
     */
    public function buildModexApiClient(): array
    {
        foreach ($this->modelPreferences as $modelPreference) {

            $credentials = Registry::credentials()->get($modelPreference->getCredentials(), true);
            if ($credentials === null) {
                continue;
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
                    ]
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
