<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\Internal;

use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
use DigitalAnomaly\AlteredLogic\Credentials\CredentialsOverride;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedModelInterface;
use DigitalAnomaly\AlteredLogic\Registry\Registry;

/**
 * A reference to a particular connection to an AI Embedding provider.
 *
 * @todo - merge with ModexConnectionReference?
 */
final readonly class EmbedConnectionReference
{
    /**
     * Constructor.
     *
     * @param AiProvidersEnum|string $provider               The name of the provider being used.
     * @param string                 $model                  The name of the model being used.
     * @param string                 $serviceFingerprint     Hash representing the service (ostensibly: provider +
     *                                                       model) being used.
     * @param string                 $credentialsFingerprint Hash representing the credentials being used.
     */
    public function __construct(
        private AiProvidersEnum|string $provider,
        private string $model,
        private string $serviceFingerprint,
        private string $credentialsFingerprint,
    ) {}



    /**
     * Create a EmbedConnectionReference from a EmbedModel.
     *
     * @param EmbedModelInterface      $embedModel          The Embed model to build from.
     * @param CredentialsOverride|null $credentialsOverride The credentials to use instead of the model's own.
     * @return self
     */
    public static function fromEmbedModel(
        EmbedModelInterface $embedModel,
        ?CredentialsOverride $credentialsOverride = null,
    ): self {

        $credentialsName = $credentialsOverride?->pickCredentialsName($embedModel->getProvider())
            ?? $embedModel->getCredentials();
        $credentials = Registry::credentials()->getOrThrow($credentialsName);

        return new self(
            $embedModel->getProvider(),
            $embedModel->getModel(),
            $embedModel->serviceFingerprint(),
            $credentials->credentialsFingerprint(),
        );
    }



    /**
     * Get the provider.
     *
     * @return AiProvidersEnum|string
     */
    public function getProvider(): AiProvidersEnum|string
    {
        return $this->provider;
    }

    /**
     * Get the provider as a string.
     *
     * @return string
     */
    public function getProviderString(): string
    {
        return $this->provider instanceof AiProvidersEnum
            ? $this->provider->value
            : $this->provider;
    }

    /**
     * Get the model.
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }



    /**
     * Generate a fingerprint that differentiates this connection from others.
     *
     * @return string
     */
    public function fingerprint(): string
    {
        return "{$this->serviceFingerprint}:{$this->credentialsFingerprint}";
    }
}
