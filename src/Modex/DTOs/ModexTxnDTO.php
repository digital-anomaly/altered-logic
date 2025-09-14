<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\DTOs;

use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\StructuredMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\TextMessagePayload;
use DigitalAnomaly\Schema\HydrateSchema;

/**
 * Representation of a single multimodal transmission (request + response) to an AI provider.
 *
 * @todo - this isn't really a DTO - perhaps move logic to AbstractMultimodalClient
 */
final readonly class ModexTxnDTO
{
    /**
     * Constructor.
     *
     * @param string                 $provider The AI provider that was used.
     * @param string                 $model    The model that was used.
     * @param boolean|null           $success  Whether the request was successful or not.
     * @param ModexTxnInputDTO       $request  The input for the transmission.
     * @param ModexTxnOutputDTO|null $response The output for the transmission.
     * @param ModexTxnMetaDTO|null   $meta     Metadata about the transmission.
     */
    public function __construct(
        public string $provider,
        public string $model,
        public ?bool $success,
        public ModexTxnInputDTO $request,
        public ?ModexTxnOutputDTO $response,
        public ?ModexTxnMetaDTO $meta,
    ) {}



    /**
     * Get the most relevant response from the transmission (text, structured response etc).
     *
     * Note: This finds the first relevant payload, transforms it and returns it.
     *
     * @return mixed
     */
    public function getResponse(): mixed
    {
        foreach ($this->response->messages ?? [] as $message) {
            foreach ($message->getPayloads() as $payload) {

                // TextMessagePayload
                if ($payload instanceof TextMessagePayload) {
                    return $payload->text;
                }

                // StructuredMessagePayload
                if ($payload instanceof StructuredMessagePayload) {

                    return HydrateSchema::instantiateStructuredData(
                        $this->request->schemas->structuredResponse,
                        $payload->structuredJson
                    );
                }

                // todo - handle other payload types - image, audio etc
            }
        }

        return null;
    }

    // /**
    //  * Get the most relevant structured response JSON from the transmission.
    //  *
    //  * Note: This finds the first relevant payload, and returns it.
    //  *
    //  * @return string|null
    //  */
    // public function getStructuredResponseJson(): ?string
    // {
    //     foreach ($this->response->messages ?? [] as $message) {
    //         foreach ($message->getPayloads() as $payload) {

    //             // StructuredMessagePayload
    //             if ($payload instanceof StructuredMessagePayload) {
    //                 return $payload->structuredJson;
    //             }
    //         }
    //     }

    //     return null;
    // }
}
