<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex;

use DigitalAnomaly\AlteredLogic\AlteredLogic;
use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
use DigitalAnomaly\AlteredLogic\Exceptions\ModexException;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\ModexApiClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\ModexModelInterface;
use DigitalAnomaly\AlteredLogic\Profiles\ModexModelProfile;
use DigitalAnomaly\AlteredLogic\Registry\HasRegisteredNameTrait;
use DigitalAnomaly\AlteredLogic\Support\StringHelper;

/**
 * Trait for modex models.
 *
 * @see ModexModelInterface
 */
trait ModexModelTrait
{
    use HasRegisteredNameTrait;



    /** @var string The provider credentials to use. */
    private readonly AiProvidersEnum|string $credentials;

    /** @var class-string<ModexApiClientInterface> The API client class to use. */
    private readonly string $client;

    /** @var string The URL to use. */
    private readonly string $url;

    /** @var array<string,string> The custom headers to use. */
    private readonly array $customHeaders;

    /** @var string The model to use. */
    private readonly string $model;

    /** @var string The base-model that $model is based on (used to determine functionality). */
    private readonly string $baseModel;

    /** @var ModexModelProfile|null The model profile to use when only using this model. */
    private ?ModexModelProfile $singleModelProfile = null;



    /**
     * Behind-the-scenes constructor.
     *
     * @param AiProvidersEnum|string $credentials   The provider credentials to use.
     * @param string                 $client        The API client class to use.
     * @param string                 $url           The URL to use.
     * @param array<string,string>   $customHeaders The custom headers to use.
     * @param string                 $model         The model to use.
     * @param string|null            $baseModel     The base-model that $model is based on (used to determine
     *                                              functionality).
     * @return void
     */
    protected function storeConfiguration(
        AiProvidersEnum|string $credentials,
        string $client,
        string $url,
        array $customHeaders,
        string $model,
        ?string $baseModel = '',
    ): void {

        $this->credentials = $credentials;
        $this->client = $client;
        $this->url = $url;
        $this->customHeaders = $customHeaders;
        $this->model = $model;
        $this->baseModel = $baseModel !== null && $baseModel !== ''
            ? $baseModel
            : $model;
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

    /**
     * Get the base-model.
     *
     * @return string
     */
    public function getBaseModel(): string
    {
        return $this->baseModel;
    }



    /**
     * Build a modex model profile containing just this model.
     *
     * Will return the same object when called multiple times.
     *
     * @return ModexModelProfile
     * @throws ModexException If the model has not been registered.
     */
    public function getModelProfile(): ModexModelProfile
    {
        if (!$this->isRegistered()) {
            throw ModexException::modexModelNotRegistered($this->getModel());
        }

        return $this->singleModelProfile ??= new ModexModelProfile()->addModel($this->getRegisteredName());
    }



    /**
     * Register this modex model.
     *
     * The model's name will be used if no name is provided.
     *
     * @param string  $name      The name of the model to register.
     * @param boolean $isDefault Whether this is the default model or not (the first one is default unless another is
     *                           specified).
     * @return void
     */
    public function register(string $name = '', bool $isDefault = false): void
    {
        $name = $name !== ''
            ? $name
            : $this->getModel();

        AlteredLogic::registerModexModel($name, $this, $isDefault);
    }



    /**
     * Build a fingerprint representing the provider + service being used (ostensibly: provider + model).
     *
     * Used to store provider details, and to differentiate between different services when working out which messages
     * need to be sent.
     *
     * @return string
     */
    public function serviceFingerprint(): string
    {
        return StringHelper::generateUniquenessHash([
            'client-class' => $this->client,
            'url' => $this->url,
            'custom-headers' => $this->customHeaders,
            'model' => $this->model,
            'base-model' => $this->baseModel,
        ]);
    }
}
