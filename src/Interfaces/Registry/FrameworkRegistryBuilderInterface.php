<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Interfaces\Registry;

use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocSearcherInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocStoreInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedCacheInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedModelInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\ModexModelInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Providers\CredentialsInterface;
use DigitalAnomaly\AlteredLogic\Profiles\DocumentProfile;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedCacheProfile;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedModelProfile;
use DigitalAnomaly\AlteredLogic\Profiles\ModexModelProfile;
use Exception;

/**
 * Interface for the FrameworkRegistryBuilder classes.
 */
interface FrameworkRegistryBuilderInterface
{
    /**
     * Build specific provider credentials from configuration.
     *
     * @param string $name The provider name.
     * @return CredentialsInterface|null
     * @throws Exception If credentials could not be built.
     */
    public static function buildCredentials(string $name): ?CredentialsInterface;





    /**
     * Get the default embed model profile name from configuration.
     *
     * @param boolean $checkExists Whether to check if the profile has been defined in the configuration when set.
     * @return string|null
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultEmbedModelProfileName(bool $checkExists): ?string;

    /**
     * Import a specific embed model profile from configuration.
     *
     * @param string $name Profile name.
     * @return EmbedModelProfile|null
     * @throws Exception If profile could not be built.
     */
    public static function buildEmbedModelProfile(string $name): ?EmbedModelProfile;



    /**
     * Get the default embed model name from configuration.
     *
     * @param boolean $checkExists Whether to check if the model has been defined in the configuration when set.
     * @return string|null
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultEmbedModelName(bool $checkExists): ?string;

    /**
     * Build an EmbedModel from configuration.
     *
     * @param string $name The name of the model to build.
     * @return EmbedModelInterface|null
     * @throws Exception If the model could not be built.
     */
    public static function buildEmbedModel(string $name): ?EmbedModelInterface;





    /**
     * Get the default embed cache profile name from configuration.
     *
     * @param boolean $checkExists Whether to check if the profile has been defined in the configuration when set.
     * @return string|null
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultEmbedCacheProfileName(bool $checkExists): ?string;

    /**
     * Import a specific embed cache profile from configuration.
     *
     * @param string $name Profile name.
     * @return EmbedCacheProfile|null
     * @throws Exception If profile could not be built.
     */
    public static function buildEmbedCacheProfile(string $name): ?EmbedCacheProfile;



    /**
     * Get the default embed cache name from configuration.
     *
     * @param boolean $checkExists Whether to check if the cache has been defined in the configuration when set.
     * @return string|null
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultEmbedCacheName(bool $checkExists): ?string;

    /**
     * Build an EmbedCache from configuration.
     *
     * @param string $name The name of the cache to build.
     * @return EmbedCacheInterface|null
     * @throws Exception If the cache could not be built.
     */
    public static function buildEmbedCache(string $name): ?EmbedCacheInterface;





    /**
     * Get the default document profile name from configuration.
     *
     * @param boolean $checkExists Whether to check if the profile has been defined in the configuration when set.
     * @return string|null
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultDocumentProfileName(bool $checkExists): ?string;

    /**
     * Import a specific document profile from configuration.
     *
     * @param string $name Profile name.
     * @return DocumentProfile|null
     * @throws Exception If profile could not be built.
     */
    public static function buildDocumentProfile(string $name): ?DocumentProfile;



    /**
     * Build a DocStore from configuration.
     *
     * @param string $name The name of the doc-store to build.
     * @return DocStoreInterface|null
     * @throws Exception If the doc-store could not be built.
     */
    public static function buildDocStore(string $name): ?DocStoreInterface;

    /**
     * Build a DocSearcher from configuration.
     *
     * @param string $name The name of the doc-searcher to build.
     * @return DocSearcherInterface|null
     * @throws Exception If the doc-searcher could not be built.
     */
    public static function buildDocSearcher(string $name): ?DocSearcherInterface;





    /**
     * Get the default modex model profile name from configuration.
     *
     * @param boolean $checkExists Whether to check if the profile has been defined in the configuration when set.
     * @return string|null
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultModexModelProfileName(bool $checkExists): ?string;

    /**
     * Import a specific modex model profile from configuration.
     *
     * @param string $name Profile name.
     * @return ModexModelProfile|null
     * @throws Exception If profile could not be built.
     */
    public static function buildModexModelProfile(string $name): ?ModexModelProfile;





    /**
     * Get the default modex model name from configuration.
     *
     * @param boolean $checkExists Whether to check if the model has been defined in the configuration when set.
     * @return string|null
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultModexModelName(bool $checkExists): ?string;

    /**
     * Build a ModexModel from configuration.
     *
     * @param string $name The name of the model to build.
     * @return ModexModelInterface|null
     * @throws Exception If the model could not be built.
     */
    public static function buildModexModel(string $name): ?ModexModelInterface;
}
