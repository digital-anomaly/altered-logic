<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Credentials;

use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
use DigitalAnomaly\AlteredLogic\Exceptions\CredentialsException;
use DigitalAnomaly\AlteredLogic\Support\StringHelper;

/**
 * A normalised, call-time credentials override.
 *
 * Represents the developer's choice of which registered credentials to use (instead of each model's own configured
 * credentials), either universally (one name for every provider) or per-provider (a map of provider name => credentials
 * name).
 *
 * The two sides of $providerMap are different kinds of thing:
 * - the keys are matched against the value each model class returns from getProvider() - they're not looked up
 *   anywhere, so an unrecognised key simply never matches, and those models fall back to their own credentials
 * - the values are registered credentials names, resolved through the Registry when the API client is built (and built
 *   from the framework's config at that point, if they haven't been registered yet)
 *
 * The practical consequence is that a mistyped map *value* throws when it's reached, whereas a mistyped map *key* is
 * indistinguishable from deliberately omitting that provider, and passes silently.
 */
final readonly class CredentialsOverride
{
    /**
     * Constructor.
     *
     * @param string|null          $universalName The credentials name to use for all providers.
     * @param array<string,string> $providerMap   Credentials names keyed by provider name.
     */
    private function __construct(
        private ?string $universalName,
        private array $providerMap,
    ) {}



    /**
     * Build a CredentialsOverride from the fluent input.
     *
     * Only the *shape* of the input is validated here - neither the provider names nor the credentials names are
     * checked for existence (see the class docblock).
     *
     * @param self|string|AiProvidersEnum|array<array-key,mixed>|null $credentials A credentials name to use for all
     *                                                                             providers, an existing override to
     *                                                                             pass through, or a map of provider
     *                                                                             name => credentials name.
     * @return self|null
     * @throws CredentialsException If the array input contains invalid keys or values.
     */
    public static function from(self|string|AiProvidersEnum|array|null $credentials): ?self
    {
        if ($credentials === null) {
            return null;
        }

        // pass an existing override straight through (used between layers)
        if ($credentials instanceof self) {
            return $credentials;
        }

        // a single provider enum applies its value universally
        if ($credentials instanceof AiProvidersEnum) {
            return new self($credentials->value, []);
        }

        // a single name applies universally ('' clears the override, matching modelProfile()'s convention)
        if (\is_string($credentials)) {
            return $credentials !== ''
                ? new self($credentials, [])
                : null;
        }

        // an array is a provider name => credentials name map
        // - the keys are matched against each model's getProvider() value later on, they're not resolved here
        $providerMap = [];
        foreach ($credentials as $provider => $name) {

            if (!\is_string($provider) || $provider === '') {
                throw CredentialsException::invalidCredentialsOverride('provider keys must be non-empty strings');
            }

            if ($name instanceof AiProvidersEnum) {
                $name = $name->value;
            }
            if (!\is_string($name) || $name === '') {
                throw CredentialsException::invalidCredentialsOverride(
                    "the credentials name for provider \"{$provider}\" must be a non-empty string or "
                    . AiProvidersEnum::class,
                );
            }

            $providerMap[$provider] = $name;
        }

        // an empty map clears the override
        return $providerMap !== []
            ? new self(null, $providerMap)
            : null;
    }



    /**
     * Pick the credentials name to use for the given provider.
     *
     * The provider is matched against the map's keys as a plain string - no registry or config lookup is involved. A
     * provider that's absent from the map and one whose key was mistyped are therefore indistinguishable: both return
     * null, and the caller falls back to the model's own credentials.
     *
     * @param AiProvidersEnum|string $provider The provider to pick credentials for.
     * @return string|null The override name, or null to use the model's own credentials.
     */
    public function pickCredentialsName(AiProvidersEnum|string $provider): ?string
    {
        if ($this->universalName !== null) {
            return $this->universalName;
        }

        $providerName = $provider instanceof AiProvidersEnum
            ? $provider->value
            : $provider;

        return $this->providerMap[$providerName] ?? null;
    }



    /**
     * Check whether this override applies one credentials name to every provider.
     *
     * Used when reporting failures, to distinguish a universal override from a provider-map entry.
     *
     * @return boolean
     */
    public function isUniversal(): bool
    {
        return $this->universalName !== null;
    }



    /**
     * Generate a stable value-based fingerprint (used to pool gated batches by override).
     *
     * @return string
     */
    public function fingerprint(): string
    {
        $map = $this->providerMap;
        \ksort($map);

        // use the full 32-char hash - a collision here would silently merge deferred batches across different keys
        return StringHelper::generateUniquenessHash(['universal' => $this->universalName, 'map' => $map], 32);
    }
}
