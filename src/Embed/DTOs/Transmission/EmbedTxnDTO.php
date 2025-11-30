<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission;

use DigitalAnomaly\AlteredLogic\Embed\Internal\EmbedConnectionReference;

// use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\StructuredMessagePayload;
// use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\TextMessagePayload;
// use DigitalAnomaly\AlteredLogic\Schemas\Schema;

/**
 * Representation of a single embed transmission (request + response) to an AI provider.
 */
final readonly class EmbedTxnDTO
{
    /**
     * Constructor.
     *
     * @param EmbedConnectionReference $connectionReference Details about the connection used.
     * @param boolean|null             $success             Whether the request was successful or not.
     * @param EmbedTxnInputDTO         $request             The input for the transmission.
     * @param EmbedTxnOutputDTO|null   $response            The output for the transmission.
     * @param EmbedTxnMetaDTO|null     $meta                Metadata about the transmission.
     */
    public function __construct(
        public EmbedConnectionReference $connectionReference,
        public ?bool $success,
        public EmbedTxnInputDTO $request,
        public ?EmbedTxnOutputDTO $response,
        public ?EmbedTxnMetaDTO $meta,
    ) {}
}
