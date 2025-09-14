<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Http\Laravel;

use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpPendingRequestInterface;
use DigitalAnomaly\AlteredLogic\Support\Http\Traits\HasCommonHttpClientTrait;

/**
 * A Laravel HTTP client.
 *
 * AI instructions (static-entry-pattern): this is the "entry" class, which boots the `/src/Support/Http/Laravel/LaravelPendingHttpRequest.php` class.
 */
final class LaravelHttpClient implements HttpClientInterface
{
    use HasCommonHttpClientTrait;



    /**
     * Get a new pending request instance.
     *
     * @return HttpPendingRequestInterface
     */
    public static function pendingRequest(): HttpPendingRequestInterface
    {
        return new LaravelPendingHttpRequest();
    }
}
