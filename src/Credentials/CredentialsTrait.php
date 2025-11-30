<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Credentials;

use DigitalAnomaly\AlteredLogic\AlteredLogic;
use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;

/**
 * Trait for AI provider credentials.
 */
trait CredentialsTrait
{
    /**
     * Register this provider credentials.
     *
     * The provider's default name will be used if no name is provided.
     *
     * @param string|AiProvidersEnum $name The name of the provider to register.
     * @return void
     */
    public function register(string|AiProvidersEnum $name = ''): void
    {
        AlteredLogic::registerCredentials($name, $this);
    }
}
