<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Http\Curl;

use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpPendingRequestInterface;
use DigitalAnomaly\AlteredLogic\Support\Http\Traits\HasCommonHttpClientTrait;

/**
 * A cURL HTTP client.
 *
 * AI instructions (static-entry-pattern): this is the "entry" class, which boots the `/src/Support/Http/Curl/CurlPendingHttpRequest.php` class.
 */
final class CurlHttpClient implements HttpClientInterface
{
    use HasCommonHttpClientTrait;



    /**
     * Get a new pending request instance.
     *
     * @return HttpPendingRequestInterface
     */
    public static function pendingRequest(): HttpPendingRequestInterface
    {
        return new CurlPendingHttpRequest();
    }
}
