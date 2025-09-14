<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Modex;

use DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Modex\OpenAiModexModel;
use DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Modex\Transformers\OpenAiResponsesApiInboundResponseTokenUsageTransformer;
use DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Modex\Transformers\OpenAiResponsesApiInboundResponseTransformer;
use DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Modex\Transformers\OpenAiResponsesApiOutboundRequestTransformer;
use DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\OpenAiCredentials;
use DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\OpenAiHelper;
use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpPendingRequestInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\ModexApiClientInterface;
use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTxnDTO;
use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTxnInputDTO;
use DigitalAnomaly\AlteredLogic\Modex\Internal\Traits\Client\BuildsModexTxnTrait;
use DigitalAnomaly\AlteredLogic\Modex\Messages\AiMessage;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DeveloperMessage;
use DigitalAnomaly\AlteredLogic\Modex\Messages\UserMessage;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpTxnDTO;
use DigitalAnomaly\Schema\Types\ArrayType;
use DigitalAnomaly\Schema\Types\EnumType;
use DigitalAnomaly\Schema\Types\NativeType;
use DigitalAnomaly\Schema\Types\Type;
use Throwable;

/**
 * Client for the OpenAI Responses API.
 */
final class OpenAiResponsesApiClient implements ModexApiClientInterface
{
    use BuildsModexTxnTrait;



    /** @var string The AI provider's identifier. */
    public const string PROVIDER_IDENTIFIER = AiProvidersEnum::OpenAI->value;

    /** @var array<class-string,string> The roles for the messages. */
    private const array MESSAGE_ROLES = [
        DeveloperMessage::class => 'developer',
        AiMessage::class => 'assistant',
        UserMessage::class => 'user',
    ];

    /** @var string The url to use when making requests. */
    private readonly string $url;

    /** @var array<string,string> The custom headers to use. */
    private readonly array $customHeaders;

    /** @var string The model to use for the OpenAI API. */
    private readonly string $model;

    /** @var string The base-model that $model is based on. */
    private readonly string $baseModel; // todo - implement or remove

    /** @var string The API key to use for the OpenAI API. */
    private readonly string $apiKey;

    /** @var string The organisation to use for the OpenAI API. */
    private readonly string $organisation;

    /** @var string The project id to use for the OpenAI API. */
    private readonly string $projectId;



    /**
     * Constructor.
     *
     * @param OpenAiModexModel  $modexModel  The modex model to use.
     * @param OpenAiCredentials $credentials The provider credentials to use.
     */
    public function __construct(
        OpenAiModexModel $modexModel,
        OpenAiCredentials $credentials
    ) {
        // todo - accept other sorts of (equivalent) Models or Providers
        //        and extract the relevant details from them
        //        e.g. Azure (with their "deployment" property)

        $this->url = $modexModel->getUrl();
        $this->customHeaders = $modexModel->getCustomHeaders();
        $this->model = $modexModel->getModel();
        $this->baseModel = $modexModel->getBaseModel();
        $this->apiKey = $credentials->getApiKey();
        $this->organisation = $credentials->getOrganisation();
        $this->projectId = $credentials->getProjectId();
    }





    /**
     * Build body of the request to send to the AI provider.
     *
     * @param ModexTxnInputDTO    $modexInput     The ModexTxnInputDTO to use.
     * @param string|integer|null $prevResponseId The id of the previous response, for conversation continuation.
     * @return string
     */
    public function buildRequestBody(ModexTxnInputDTO $modexInput, string|int|null $prevResponseId): string
    {
        return OpenAiResponsesApiOutboundRequestTransformer::buildRequestBody(
            $modexInput,
            $this->model,
            self::MESSAGE_ROLES,
            $prevResponseId,
            $this->resolveWrapSuffix($modexInput->schemas->structuredResponse?->type),
        );
    }





    /**
     * Send the request to the AI provider.
     *
     * @param HttpClientInterface|HttpPendingRequestInterface $httpClient  The HTTP client to use to send the request.
     * @param string                                          $requestBody The request body to send.
     * @return HttpTxnDTO
     */
    public function sendRequest(
        HttpClientInterface|HttpPendingRequestInterface $httpClient,
        string $requestBody,
    ): HttpTxnDTO {

        // $httpTxn = unserialize(base64_decode('...'));

        if (isset($httpTxn)) {
            /** @var httpTxnDTO $httpTxn */
            return $httpTxn;
        }



        /** @var array<string,mixed> $bodyData */
        $bodyData = \json_decode($requestBody, true);

        $httpClient = OpenAiHelper::prepareHttpClient(
            $httpClient,
            $this->apiKey,
            $this->organisation,
            $this->projectId,
            $this->customHeaders,
        );

        $httpTxn = $httpClient->post($this->url, $bodyData);

        // print base64_encode(serialize($httpTxn));
        // exit;

        return $httpTxn;
    }





    /**
     * Build an ModexTxnDTO based on the response from the AI provider.
     *
     * @param ModexTxnInputDTO $modexInput The ModexTxnInputDTO used.
     * @param HttpTxnDTO       $httpTxn    The transmission to analyse.
     * @return ModexTxnDTO
     */
    public function buildResponse(ModexTxnInputDTO $modexInput, HttpTxnDTO $httpTxn): ModexTxnDTO
    {
        $responseData = $this->jsonDecodeResponse($httpTxn->response->body ?? '');

        $transmissionOutput = (new OpenAiResponsesApiInboundResponseTransformer(
            $httpTxn->response,
            $responseData,
            $this->resolveWrapSuffix($modexInput->schemas->structuredResponse?->type) !== null,
        ))->transformResponse();

        $tokenUsage = OpenAiResponsesApiInboundResponseTokenUsageTransformer::buildTokenUsage(
            $responseData,
        );

        return $this->buildModexTxn(
            self::PROVIDER_IDENTIFIER,
            $this->model,
            $httpTxn,
            $modexInput,
            $transmissionOutput,
            $tokenUsage,
        );
    }

    /**
     * Json decode the response body.
     *
     * @param string $responseBody The response body to decode.
     * @return array<string,mixed>
     */
    private function jsonDecodeResponse(string $responseBody): array
    {
        try {

            $return = \json_decode($responseBody, true);

            return \is_array($return)
                ? $return
                : [];

        } catch (Throwable) {
            return [];
        }
    }





    /**
     * Check to see if the response should be wrapped in a class. Determine the suffix of the element to wrap it in.
     *
     * @param Type|null $type The type to check.
     * @return string|null
     */
    private function resolveWrapSuffix(?Type $type): ?string
    {
        return match (true) {
            $type instanceof ArrayType => 'Array',
            $type instanceof EnumType => 'Enum',
            $type instanceof NativeType => 'Wrap',
            default => null,
        };
    }
}
