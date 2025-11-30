<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI;

use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
use DigitalAnomaly\AlteredLogic\Credentials\AbstractCredentials;
use DigitalAnomaly\AlteredLogic\Support\StringHelper;

/**
 * OpenAI provider credentials.
 */
final readonly class OpenAiCredentials extends AbstractCredentials
{
    /**
     * Constructor.
     *
     * @param string $apiKey       The API key to use.
     * @param string $organisation The organisation to use.
     * @param string $projectId    The project id to use.
     */
    public function __construct(
        private string $apiKey,
        private string $organisation = '',
        private string $projectId = '',
    ) {}



    /**
     * Get the provider's default name.
     *
     * This value may come from {@see AiProvidersEnum}, or a custom string value.
     *
     * @return AiProvidersEnum
     */
    public function getDefaultName(): AiProvidersEnum
    {
        return AiProvidersEnum::OpenAI;
    }

    /**
     * Get the API key.
     *
     * Note: This is not in the interface because it's specific to OpenAI. The OpenAI clients know to use this method.
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Get the organisation.
     *
     * Note: This is not in the interface because it's specific to OpenAI. The OpenAI clients know to use this method.
     *
     * @return string
     */
    public function getOrganisation(): string
    {
        return $this->organisation;
    }

    /**
     * Get the project ID.
     *
     * Note: This is not in the interface because it's specific to OpenAI. The OpenAI clients know to use this method.
     *
     * @return string
     */
    public function getProjectId(): string
    {
        return $this->projectId;
    }



    /**
     * Build a fingerprint representing a provider + service's credentials being used.
     *
     * Used to store provider details, and to differentiate between different credentials for a provider + service when
     * working out which messages need to be sent.
     *
     * @return string
     */
    public function credentialsFingerprint(): string
    {
        return StringHelper::generateUniquenessHash([
            'apiKey' => $this->apiKey,
            'organisation' => $this->organisation,
            'projectId' => $this->projectId,
        ]);
    }
}
