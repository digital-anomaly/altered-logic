<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\Resolvers;

use CodeDistortion\Backoff\Backoff;
use DigitalAnomaly\AlteredLogic\Common\Enums\HttpClientEnum;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpPendingRequestInterface;
use DigitalAnomaly\AlteredLogic\Support\Framework\CapabilityDetector;
use DigitalAnomaly\AlteredLogic\Support\Http\Curl\CurlHttpClient;
use DigitalAnomaly\AlteredLogic\Support\Http\Laravel\LaravelHttpClient;

/**
 * Resolves the correct HTTP client to use, based on the provider and model profile.
 */
final class HttpClientResolver
{
    /**
     * Resolve and build the HTTP client.
     *
     * @param Backoff|null $backoff The backoff to use.
     * @return HttpClientInterface|HttpPendingRequestInterface
     */
    public static function buildHttpClient(?Backoff $backoff = null): HttpClientInterface|HttpPendingRequestInterface
    {
        // todo - add other frameworks
        $framework = CapabilityDetector::pickFunctionalityToUse([
            HttpClientEnum::Laravel,
            HttpClientEnum::Curl,
        ]);

        $client = match ($framework) {
            HttpClientEnum::Laravel => LaravelHttpClient::pendingRequest(),
            HttpClientEnum::Curl => CurlHttpClient::pendingRequest(),
            default => throw new \Exception('The HTTP client could not be resolved'), // todo - add a custom exception
        };

        $client->withConnectTimeout(3)
            ->withReceiveTimeout(120)
            ->withBackoff($backoff);

        return $client;
    }
}
