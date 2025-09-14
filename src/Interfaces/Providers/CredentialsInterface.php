<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Interfaces\Providers;

use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;

/**
 * Interface for a provider credential details.
 */
interface CredentialsInterface
{
    /**
     * Get the provider's default name.
     *
     * This value may come from {@see AiProvidersEnum}, or a custom string value.
     *
     * @return string|AiProvidersEnum
     */
    public function getDefaultName(): string|AiProvidersEnum;

    /**
     * Register the AI provider credentials.
     *
     * The provider's default name will be used if no name is provided.
     *
     * @param string $name The name of the provider to register.
     * @return void
     */
    public function register(string $name = ''): void;
}
