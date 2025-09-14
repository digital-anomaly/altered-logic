<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Interfaces\Embed;

use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
use DigitalAnomaly\AlteredLogic\Embed\EmbedFaker;

/**
 * Interface for an embed model.
 */
interface EmbedModelInterface
{
    /**
     * Get the provider credentials.
     *
     * @return AiProvidersEnum|string
     */
    public function getCredentials(): AiProvidersEnum|string;

    /**
     * Get the client class.
     *
     * @return string
     */
    public function getClientClass(): string;

    /**
     * Get the URL.
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Get the custom headers.
     *
     * @return array<string,string>
     */
    public function getCustomHeaders(): array;

    /**
     * Get the model.
     *
     * @return string
     */
    public function getModel(): string;

    // /**
    //  * Get the base-model.
    //  *
    //  * @return string
    //  */
    // public function getBaseModel(): string;

    /**
     * Get the dimensions.
     *
     * @return integer
     */
    public function getDimensions(): int;


    /**
     * Get the faker.
     *
     * @return EmbedFaker|null
     */
    public function getFaker(): ?EmbedFaker;



    /**
     * Set the faker to use when generating embeddings.
     *
     * @param EmbedFaker $faker The faker to use when generating embeddings.
     * @return self
     */
    public function faker(EmbedFaker $faker): self;



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
    public function register(string $name = '', bool $isDefault = false): void;
}
