<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Profiles;

use DigitalAnomaly\AlteredLogic\AlteredLogic;
use DigitalAnomaly\AlteredLogic\Exceptions\EmbedException;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedApiClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedModelInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Registry\HasDefaultNameInterface;
use DigitalAnomaly\AlteredLogic\Registry\Registry;
use DigitalAnomaly\AlteredLogic\Support\Framework\DependencyInjection;

/**
 * A profile containing a priority list of embed models to use.
 */
final class EmbedModelProfile implements HasDefaultNameInterface
{
    /** @var EmbedModelInterface[] The models to use in order of preference. */
    private array $modelPreferences = [];



    /**
     * Add a model to the preference list.
     *
     * @param EmbedModelInterface $model The model to add.
     * @return self
     */
    public function addModel(EmbedModelInterface $model): self
    {
        $this->modelPreferences[] = $model;

        return $this;
    }



    /**
     * Get the profile's default name.
     *
     * @return string
     */
    public function getDefaultName(): string
    {
        return $this->getModelName();
    }

    /**
     * Get the model name.
     *
     * @return string
     */
    public function getModelName(): string
    {
        // pick the first model's name
        $embedModel = $this->modelPreferences[0] ?? null;

        return $embedModel?->getModel() ?? 'default';
    }

    /**
     * Get the dimensions.
     *
     * @return integer
     */
    public function getDimensions(): int
    {
        // pick the first model's dimensions
        $embedModel = $this->modelPreferences[0] ?? null;

        return $embedModel?->getDimensions() ?? 0;
    }





    /**
     * Resolve and build the Embed API client.
     *
     * @return array{apiClient:EmbedApiClientInterface,embedModel:EmbedModelInterface}
     * @throws EmbedException If the embed API client could not be resolved.
     */
    public function buildEmbedApiClient(): array
    {
        foreach ($this->modelPreferences as $modelPreference) {

            $credentials = Registry::credentials()->get($modelPreference->getCredentials(), true);
            if ($credentials === null) {
                continue;
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
     * Register the embed model.
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
