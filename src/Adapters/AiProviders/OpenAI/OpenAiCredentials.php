<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI;

use DigitalAnomaly\AlteredLogic\Adapters\AiProviders\CredentialsTrait;
use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
use DigitalAnomaly\AlteredLogic\Interfaces\Providers\CredentialsInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Registry\HasDefaultNameInterface;

/**
 * OpenAI provider credentials.
 */
final readonly class OpenAiCredentials implements CredentialsInterface, HasDefaultNameInterface
{
    use CredentialsTrait;



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
     * This value may come from {@see ProvidersEnum}, or a custom string value.
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
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Get the organisation.
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
     * @return string
     */
    public function getProjectId(): string
    {
        return $this->projectId;
    }
}
