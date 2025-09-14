<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Http\Laravel;

use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpPendingRequestInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpStreamInterface;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\DurationDTO;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpRequestDTO;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpResponseDTO;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpTxnDTO;
use DigitalAnomaly\AlteredLogic\Support\Http\Traits\HasCommonHttpTrait;
use DigitalAnomaly\AlteredLogic\Support\Laravel\LaravelEventListener;
use Illuminate\Http\Client\ConnectionException as LaravelConnectionException;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\PendingRequest as LaravelPendingRequest;
use Illuminate\Http\Client\Request as LaravelRequest;
use Illuminate\Http\Client\Response as LaravelResponse;
use Illuminate\Support\Facades\Http;

/**
 * A pending Laravel HTTP request.
 *
 * AI instructions (static-entry-pattern): This is the "enterable" class, "entered" by the LaravelHttpClient class.
 */
final class LaravelPendingHttpRequest implements HttpPendingRequestInterface
{
    use HasCommonHttpTrait;



    /**
     * Send a request.
     *
     * @param string              $method The HTTP method to send the request with.
     * @param string              $url    The url to send the request to.
     * @param array<string,mixed> $params The parameters to send with the request.
     * @return HttpTxnDTO
     */
    protected function send(string $method, string $url, array $params = []): HttpTxnDTO
    {
        $laravelRequest = $laravelResponse = null;
        $this->registerRequestListener($laravelRequest);

        $http = $this->buildLaravelHttpClient();

        $start = \microtime(true);

        try {
            $laravelResponse = match ($method) {
                'POST' => $http->post($url, $params),
                default => $http->get($url, $params), // GET
            };
        } catch (LaravelConnectionException $e) {
            // swallow the exception
        } finally {
            $stop = \microtime(true);
            $this->removeRequestListener();
        }



        if ($laravelRequest !== null) {
            $url = $laravelRequest->url(); // get the updated URL containing query parameters
        }

        $httpTxn = $this->buildNewHttpTxn(
            $method,
            $url,
            $params,
            $start,
            $stop,
            $laravelRequest,
            $laravelResponse,
        );

        $this->setHttpTxn($httpTxn);

        return $httpTxn;
    }





    /**
     * Build a Laravel pending request.
     *
     * @return LaravelPendingRequest
     */
    private function buildLaravelHttpClient(): LaravelPendingRequest
    {
        $headers = $this->getHeaders();
        $headers['User-Agent'] = $this->buildUserAgent('Laravel', \app()->version());

        $http = Http::withHeaders($headers)
            ->connectTimeout($this->getConnectionTimeout())
            ->timeout($this->getReceiveTimeout());

        $basicAuth = $this->getBasicAuth();
        if (\is_array($basicAuth)) {
            $http->withBasicAuth($basicAuth[0], $basicAuth[1]);
        }

        if (\is_string($this->getBearerToken())) {
            $http->withToken($this->getBearerToken());
        }

        return $http;
    }

    /**
     * Register a listener to capture the Laravel request.
     *
     * @param LaravelRequest|null &$laravelRequest The Laravel request.
     * @return void
     */
    private function registerRequestListener(?LaravelRequest &$laravelRequest): void
    {
        $requestSendingCallback = function (RequestSending $event) use (&$laravelRequest) {
            $laravelRequest = $event->request;
        };

        LaravelEventListener::registerListener(__CLASS__, RequestSending::class, $requestSendingCallback);
    }

    /**
     * Remove the listener that captures the Laravel request.
     *
     * @return void
     */
    private function removeRequestListener(): void
    {
        LaravelEventListener::removeListeners(__CLASS__);
    }





    /**
     * Build a transmission summary.
     *
     * @param string               $method          The HTTP method to send the request with.
     * @param string               $url             The url to send the request to.
     * @param array<string,mixed>  $params          The parameters to send with the request.
     * @param float                $start           The start time.
     * @param float                $stop            The stop time.
     * @param LaravelRequest|null  $laravelRequest  The Laravel request.
     * @param LaravelResponse|null $laravelResponse The Laravel response.
     * @return HttpTxnDTO
     */
    private function buildNewHttpTxn(
        string $method,
        string $url,
        array $params,
        float $start,
        float $stop,
        ?LaravelRequest $laravelRequest,
        ?LaravelResponse $laravelResponse,
    ): HttpTxnDTO {

        /** @var array<string,string[]> $requestHeaders */
        $requestHeaders = $laravelRequest?->headers() ?? [];
        $requestHeaders = $this->maskAuthHeaders($requestHeaders);

        $requestDTO = new HttpRequestDTO(
            $method,
            $url,
            $params,
            $requestHeaders,
            $laravelRequest?->body() ?? '',
        );



        /** @var array<string,string[]> $responseHeaders */
        $responseHeaders = $laravelResponse?->headers() ?? [];

        $responseDTO = $laravelResponse !== null
            ? new HttpResponseDTO(
                $laravelResponse->status(),
                $laravelResponse->reason(),
                $responseHeaders,
                $laravelResponse->body(),
            )
            : null;



        return new HttpTxnDTO(
            $requestDTO,
            $responseDTO,
            DurationDTO::new($start, $stop)
        );
    }





    /**
     * Send a request, and stream the response.
     *
     * @param string              $method The HTTP method to send the request with.
     * @param string              $url    The url to send the request to.
     * @param array<string,mixed> $params The GET parameters to send with the request.
     * @return HttpStreamInterface
     */
    private function streamSend(string $method, string $url, array $params = []): HttpStreamInterface
    {
        // todo
    }





    // /**
    //  * Build an HttpTxnDTO representing the current state of the client.
    //  *
    //  * This is useful when streaming responses (as the stream itself is what's returned originally. e.g.
    //  * ->streamPost()).
    //  *
    //  * @return HttpTxnDTO|null
    //  */
    // public function httpTxn(): ?HttpTxnDTO
    // {
    //     return $this->httpTxn;
    // }
}
