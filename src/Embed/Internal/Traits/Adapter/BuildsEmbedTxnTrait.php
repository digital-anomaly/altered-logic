<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\Internal\Traits\Adapter;

use DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission\EmbedTokenUsageDTO;
use DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission\EmbedTxnDTO;
use DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission\EmbedTxnInputDTO;
use DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission\EmbedTxnMetaDTO;
use DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission\EmbedTxnOutputDTO;
use DigitalAnomaly\AlteredLogic\Support\DTOs\ActiveDurationDTO;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpTxnDTO;

/**
 * Builds an EmbedTxnDTO, the result of an embed request.
 */
trait BuildsEmbedTxnTrait
{
    /**
     * Build a MultimodalTransmissionDTO now that we have the response details.
     *
     * @param string                 $provider    The provider used.
     * @param string                 $model       The model that was used.
     * @param HttpTxnDTO             $httpTxn     The HTTP transmission details.
     * @param EmbedTxnInputDTO       $embedInput  The EmbedTxnInputDTO used.
     * @param EmbedTxnOutputDTO|null $embedOutput The output details generated.
     * @param EmbedTokenUsageDTO     $tokenUsage  The token usage details.
     * @return EmbedTxnDTO
     */
    protected function buildEmbedTxn(
        string $provider,
        string $model,
        HttpTxnDTO $httpTxn,
        EmbedTxnInputDTO $embedInput,
        ?EmbedTxnOutputDTO $embedOutput,
        EmbedTokenUsageDTO $tokenUsage,
    ): EmbedTxnDTO {

        $activeDuration = ActiveDurationDTO::new(
            $httpTxn->duration->startTimestamp,
            $httpTxn->duration->endTimestamp,
        );

        $transmissionMeta = new EmbedTxnMetaDTO(
            $activeDuration,
            $tokenUsage,
        );

        return new EmbedTxnDTO(
            $provider,
            $model,
            ($httpTxn->response?->wasSuccessful() ?? false) && $embedOutput !== null,
            $embedInput,
            $embedOutput,
            $transmissionMeta,
        );
    }
}
