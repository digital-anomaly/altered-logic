<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\AiProviders;

use DigitalAnomaly\AlteredLogic\AlteredLogic;
use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
use DigitalAnomaly\AlteredLogic\Embed\EmbedFaker;
use DigitalAnomaly\AlteredLogic\Exceptions\EmbedException;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedApiClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedModelInterface;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedModelProfile;

/**
 * Trait for embed models.
 *
 * @see EmbedModelInterface
 */
trait EmbedModelTrait
{
    /** @var string The provider credentials to use. */
    private readonly AiProvidersEnum|string $credentials;

    /** @var class-string<EmbedApiClientInterface> The API client class to use. */
    private readonly string $client;

    /** @var string The URL to use. */
    private readonly string $url;

    /** @var array<string,string> The custom headers to use. */
    private readonly array $customHeaders;

    /** @var string The model to use. */
    private readonly string $model;

    // /** @var string The base-model that $model is based on (used to determine functionality). */
    // private readonly string $baseModel;

    /** @var integer The number of dimensions the embeddings have. */
    private readonly int $dimensions;

    /** @var EmbedFaker|null The faker to use when generating embeddings. */
    private ?EmbedFaker $faker = null;



    /**
     * Configuration for the constructor to use.
     *
     * @param AiProvidersEnum|string                $credentials   The provider credentials to use.
     * @param class-string<EmbedApiClientInterface> $client        The API client class to use.
     * @param string                                $url           The URL to use.
     * @param string                                $model         The model to use.
     * @param integer                               $dimensions    The number of dimensions the embeddings have.
     * @param array<string,string>                  $customHeaders The custom headers to use.
     * @return void
     */
    protected function storeConfiguration(
        AiProvidersEnum|string $credentials,
        string $client,
        string $url,
        string $model,
        // ?string $baseModel = '',
        int $dimensions,
        array $customHeaders,
    ): void {

        $this->credentials = $credentials;
        $this->client = $client;
        $this->url = $url;
        $this->customHeaders = $customHeaders;
        $this->dimensions = $dimensions;
        $this->model = $model;
        // $this->baseModel = $baseModel !== null && $baseModel !== ''
        //     ? $baseModel
        //     : $model;
    }



    /**
     * Get the provider credentials.
     *
     * @return AiProvidersEnum|string
     */
    public function getCredentials(): AiProvidersEnum|string
    {
        return $this->credentials;
    }

    /**
     * Get the client class.
     *
     * @return string
     */
    public function getClientClass(): string
    {
        return $this->client;
    }

    /**
     * Get the URL.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get the custom headers.
     *
     * @return array<string,string>
     */
    public function getCustomHeaders(): array
    {
        return $this->customHeaders;
    }

    /**
     * Get the model.
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    // /**
    //  * Get the base-model.
    //  *
    //  * @return string
    //  */
    // public function getBaseModel(): string
    // {
    //     return $this->baseModel;
    // }

    /**
     * Get the dimensions.
     *
     * @return integer
     */
    public function getDimensions(): int
    {
        return $this->dimensions;
    }



    /**
     * Get the faker.
     *
     * @return EmbedFaker|null
     */
    public function getFaker(): ?EmbedFaker
    {
        return $this->faker;
    }



    /**
     * Set the faker to use when generating embeddings.
     *
     * @param EmbedFaker $faker The faker to use when generating embeddings.
     * @return $this
     */
    public function faker(EmbedFaker $faker): static
    {
        $this->faker = $faker;

        return $this;
    }



    /**
     * Register the embed model.
     *
     * The model name will be used if no name is provided.
     *
     * @param string  $name      The name of the model to register.
     * @param boolean $isDefault Whether this is the default model or not (the first one is default unless another is
     *                           specified).
     * @return void
     */
    public function register(string $name = '', bool $isDefault = false): void
    {
        // create an EmbedModelProfile with one EmbedModel
        // abstracting via a EmbedModelProfile is for future enhancement. e.g.:
        // - if the same model is supported via different end-points, provide fall-back if one isn't available
        // but for now, having one EmbedModel per profile is a sane starting point

        $modelProfile = new EmbedModelProfile()->addModel($this);

        AlteredLogic::registerEmbedModelProfile($name, $modelProfile, $isDefault);
    }



    /**
     * Get the default dimensions for a given model.
     *
     * @param string                $model             The model to get the default dimensions for.
     * @param array<string,integer> $defaultDimensions The default dimensions for each model.
     * @return integer
     * @throws EmbedException If the dimensions are unknown for the given model.
     */
    private static function pickDefaultDimensions(string $model, array $defaultDimensions): int
    {
        if (\array_key_exists($model, $defaultDimensions)) {
            return $defaultDimensions[$model];
        }

        throw EmbedException::dimensionsUnknown($model);
    }
}
