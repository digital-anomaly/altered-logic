<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Credentials;

use DigitalAnomaly\AlteredLogic\Credentials\CredentialsTrait;
use DigitalAnomaly\AlteredLogic\Interfaces\Providers\CredentialsInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Registry\HasDefaultNameInterface;

/**
 * Abstract class that represents an AI provider credentials.
 */
abstract readonly class AbstractCredentials implements CredentialsInterface, HasDefaultNameInterface
{
    use CredentialsTrait;
}
