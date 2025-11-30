<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Embed;

use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
use DigitalAnomaly\AlteredLogic\Embed\AbstractEmbedModel;
use DigitalAnomaly\AlteredLogic\Exceptions\EmbedException;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedApiClientInterface;

/**
 * Represents an OpenAI embedding model.
 */
final class OpenAiEmbedModel extends AbstractEmbedModel
{
    /** @var array<string,integer> The default dimensions for each embedding model. */
    private const array DEFAULT_MODEL_DIMENSIONS = [
        'text-embedding-3-small' => 1536,
        'text-embedding-3-large' => 3072,
        'text-embedding-ada-002' => 1536,
    ];



    /**
     * Constructor.
     *
     * @param AiProvidersEnum|string                $credentials   The provider credentials to use.
     * @param class-string<EmbedApiClientInterface> $client        The API client class to use.
     * @param string                                $url           The URL to use.
     * @param string                                $model         The model to use.
     * @param integer|null                          $dimensions    The number of dimensions the model produces.
     * @param array<string,string>                  $customHeaders The custom headers to use.
     * @throws EmbedException If the dimensions for the given model aren't known.
     */
    public function __construct(
        AiProvidersEnum|string $credentials,
        string $client,
        string $url,
        string $model,
        ?int $dimensions = null,
        array $customHeaders = [],
    ) {

        if ($dimensions === null) {
            $dimensions = self::pickDefaultDimensions($model, self::DEFAULT_MODEL_DIMENSIONS);
        }

        $this->storeConfiguration(
            $credentials,
            $client,
            $url,
            $model,
            $dimensions,
            $customHeaders,
        );
    }

    /**
     * Get the provider.
     *
     * @return AiProvidersEnum|string
     */
    public function getProvider(): AiProvidersEnum|string
    {
        return AiProvidersEnum::OpenAI;
    }
}
