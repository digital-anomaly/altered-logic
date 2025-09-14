<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Embed;

use DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Embed\Transformers\OpenAiEmbedApiInboundResponseTransformer;
use DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\OpenAiHelper;
use DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\OpenAiCredentials;
use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
use DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission\EmbedTokenUsageDTO;
use DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission\EmbedTxnDTO;
use DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission\EmbedTxnInputDTO;
use DigitalAnomaly\AlteredLogic\Embed\Internal\Traits\Adapter\BuildsEmbedTxnTrait;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedApiClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Http\HttpPendingRequestInterface;
use DigitalAnomaly\AlteredLogic\Support\ArrayHelper;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpTxnDTO;
use Throwable;

/**
 * Client for the OpenAI Embeddings API.
 */
final class OpenAiEmbedApiClient implements EmbedApiClientInterface
{
    use BuildsEmbedTxnTrait;



    /** @var string The AI provider's identifier. */
    public const string PROVIDER_IDENTIFIER = AiProvidersEnum::OpenAI->value;

    /** @var string The url to use when making requests. */
    private readonly string $url;

    /** @var array<string,string> The custom headers to use. */
    private readonly array $customHeaders;

    /** @var string The model to use for the OpenAI API. */
    private readonly string $model;

    /** @var integer The dimensions for the embeddings. */
    private readonly int $dimensions;

    /** @var string The API key to use for the OpenAI API. */
    private readonly string $apiKey;

    /** @var string The organisation to use for the OpenAI API. */
    private readonly string $organisation;

    /** @var string The project id to use for the OpenAI API. */
    private readonly string $projectId;



    /**
     * Constructor.
     *
     * @param OpenAiEmbedModel  $embedModel  The embed model to use.
     * @param OpenAiCredentials $credentials The provider credentials to use.
     */
    public function __construct(
        OpenAiEmbedModel $embedModel,
        OpenAiCredentials $credentials,
    ) {
        // todo - accept other sorts of (equivalent) Models or Providers
        //        and extract the relevant details from them
        //        e.g. Azure (with their "deployment" property)

        $this->url = $embedModel->getUrl();
        $this->customHeaders = $embedModel->getCustomHeaders();
        $this->model = $embedModel->getModel();
        $this->dimensions = $embedModel->getDimensions();
        $this->apiKey = $credentials->getApiKey();
        $this->organisation = $credentials->getOrganisation();
        $this->projectId = $credentials->getProjectId();
    }





    /**
     * Build body of the request to send to the AI provider.
     *
     * @param EmbedTxnInputDTO $embedInput The EmbedTxnInputDTO to use.
     * @return string
     */
    public function buildRequestBody(EmbedTxnInputDTO $embedInput): string
    {
        $inputs = \array_values($embedInput->inputs);

        $bodyData = [

            // 'input' and 'model' are required

            'input' => self::buildEmbedInputs($inputs),
            'model' => $this->model,

            // the rest are optional
            // (and are in alphabetical from here on down. the order doesn't matter but it matches the docs)

            'dimensions' => $this->dimensions,
            'encoding_format' => null, // float
            'user' => null,
        ];

        $bodyData = ArrayHelper::removeEmptyElements($bodyData);

        return (string) \json_encode($bodyData);
    }

    /**
     * Build the input to embed, for the request.
     *
     * @param string[] $inputs The inputs to use for the request.
     * @return string|string[]
     */
    private static function buildEmbedInputs(array $inputs): string|array
    {
        $return = $inputs;

        // …

        return (\count($return) <= 1)
            ? $return[0] ?? ''
            : $return;
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

        // /** @var httpTxnDTO $httpTxn */
        // $httpTxn = unserialize(base64_decode('..'));

        // dd($httpTxn);



        /** @var array<string,mixed> $bodyData */
        $bodyData = \json_decode($requestBody, true);

        $httpClient = OpenAiHelper::prepareHttpClient(
            $httpClient,
            $this->apiKey,
            $this->organisation,
            $this->projectId,
            $this->customHeaders,
        );

        $httpTxn ??= $httpClient->post($this->url, $bodyData);

        // print base64_encode(serialize($httpTxn));
        // exit;

        return $httpTxn;
    }





    /**
     * Build an EmbedTxnDTO based on the response from the AI provider.
     *
     * @param EmbedTxnInputDTO $embedInput The EmbedsTxnInputDTO used.
     * @param HttpTxnDTO       $httpTxn    The transmission to analyse.
     * @return EmbedTxnDTO
     */
    public function buildResponse(EmbedTxnInputDTO $embedInput, HttpTxnDTO $httpTxn): EmbedTxnDTO
    {
        $responseData = $this->jsonDecodeResponse($httpTxn->response->body ?? '');

        $transmissionOutput = (new OpenAiEmbedApiInboundResponseTransformer(
            $httpTxn->response,
            $responseData,
        ))->transformResponse();

        return $this->buildEmbedTxn(
            self::PROVIDER_IDENTIFIER,
            $this->model,
            $httpTxn,
            $embedInput,
            $transmissionOutput,
            self::buildTokenUsage($responseData),
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
     * Build a token usage DTO based on the response data.
     *
     * @param array<string,mixed> $responseData The response data to build the token usage DTO from.
     * @return EmbedTokenUsageDTO
     */
    private function buildTokenUsage(array $responseData): EmbedTokenUsageDTO
    {
        $tokenUsageData = (array) ($responseData['usage'] ?? []);

        return new EmbedTokenUsageDTO(
            (int) ($tokenUsageData['prompt_tokens'] ?? 0),
            // (int) ($tokenUsageData['total_tokens'] ?? 0),
            // (int) ($tokenUsageData['input_tokens_details']['cached_tokens'] ?? 0),
            // (int) ($tokenUsageData['output_tokens_details']['reasoning_tokens'] ?? 0),
        );
    }
}
