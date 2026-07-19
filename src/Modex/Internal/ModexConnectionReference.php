<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal;

use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
use DigitalAnomaly\AlteredLogic\Credentials\CredentialsOverride;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\ModexModelInterface;
use DigitalAnomaly\AlteredLogic\Registry\Registry;

/**
 * A reference to a particular connection to an AI LLM provider.
 *
 * @todo - merge with EmbedConnectionReference?
 */
final readonly class ModexConnectionReference
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
     * Create a ModexConnectionReference from a ModexModel.
     *
     * @param ModexModelInterface      $modexModel          The Modex model to build from.
     * @param CredentialsOverride|null $credentialsOverride The credentials to use instead of the model's own.
     * @return self
     */
    public static function fromModexModel(
        ModexModelInterface $modexModel,
        ?CredentialsOverride $credentialsOverride = null,
    ): self {

        $credentialsName = $credentialsOverride?->pickCredentialsName($modexModel->getProvider())
            ?? $modexModel->getCredentials();
        $credentials = Registry::credentials()->getOrThrow($credentialsName);

        return new self(
            $modexModel->getProvider(),
            $modexModel->getModel(),
            $modexModel->serviceFingerprint(),
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
