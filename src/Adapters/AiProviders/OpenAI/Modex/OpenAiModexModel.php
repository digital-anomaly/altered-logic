<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Modex;

use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
use DigitalAnomaly\AlteredLogic\Modex\AbstractModexModel;

/**
 * Represents an OpenAI Modex model.
 */
final class OpenAiModexModel extends AbstractModexModel
{
    /**
     * Constructor.
     *
     * @param AiProvidersEnum|string $credentials   The provider credentials to use.
     * @param string                 $client        The API client class to use.
     * @param string                 $url           The URL to use.
     * @param string                 $model         The model to use.
     * @param array<string,string>   $customHeaders The custom headers to use.
     * @param string|null            $baseModel     The base-model that $model is based on (used to determine
     *                                              functionality).
     */
    public function __construct(
        AiProvidersEnum|string $credentials,
        string $client,
        string $url,
        string $model,
        array $customHeaders = [],
        ?string $baseModel = ''
    ) {

        $this->storeConfiguration(
            $credentials,
            $client,
            $url,
            $customHeaders,
            $model,
            $baseModel,
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
