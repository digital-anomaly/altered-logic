<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal\Traits\Client;

use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTokenUsageDTO;
use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTxnDTO;
use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTxnInputDTO;
use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTxnMetaDTO;
use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTxnOutputDTO;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexConnectionReference;
use DigitalAnomaly\AlteredLogic\Support\DTOs\ActiveDurationDTO;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpTxnDTO;

/**
 * Builds a ModexTxnDTO, the result of a modex request.
 */
trait BuildsModexTxnTrait
{
    /**
     * Build a MultimodalTransmissionDTO now that we have the response details.
     *
     * @param ModexConnectionReference $connectionReference Details about the connection used.
     * @param HttpTxnDTO               $httpTxn             The HTTP transmission details.
     * @param ModexTxnInputDTO         $modexInput          The ModexTxnInputDTO used.
     * @param ModexTxnOutputDTO|null   $modexOutput         The output details generated.
     * @param ModexTokenUsageDTO       $tokenUsage          The token usage details.
     * @return ModexTxnDTO
     */
    protected function buildModexTxn(
        ModexConnectionReference $connectionReference,
        HttpTxnDTO $httpTxn,
        ModexTxnInputDTO $modexInput,
        ?ModexTxnOutputDTO $modexOutput,
        ModexTokenUsageDTO $tokenUsage,
    ): ModexTxnDTO {

        $activeDuration = ActiveDurationDTO::new(
            $httpTxn->duration->startTimestamp,
            $httpTxn->duration->endTimestamp,
        );

        $transmissionMeta = new ModexTxnMetaDTO(
            $activeDuration,
            $tokenUsage,
        );

        return new ModexTxnDTO(
            $connectionReference,
            ($httpTxn->response?->wasSuccessful() ?? false) && $modexOutput !== null,
            $modexInput,
            $modexOutput,
            $transmissionMeta,
        );
    }
}
