<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Interfaces\Registry;

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
     * @return string
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultEmbedModelProfileName(): string;

    /**
     * Import a specific embed model profile from configuration.
     *
     * @param string $name Profile name.
     * @return EmbedModelProfile|null
     * @throws Exception If profile could not be built.
     */
    public static function buildEmbedModelProfile(string $name): ?EmbedModelProfile;





    /**
     * Get the default embed cache profile name from configuration.
     *
     * @return string
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultEmbedCacheProfileName(): string;

    /**
     * Import a specific embed cache profile from configuration.
     *
     * @param string $name Profile name.
     * @return EmbedCacheProfile|null
     * @throws Exception If profile could not be built.
     */
    public static function buildEmbedCacheProfile(string $name): ?EmbedCacheProfile;





    /**
     * Get the default document profile name from configuration.
     *
     * @return string
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultDocumentProfileName(): string;

    /**
     * Import a specific document profile from configuration.
     *
     * @param string $name Profile name.
     * @return DocumentProfile|null
     * @throws Exception If profile could not be built.
     */
    public static function buildDocumentProfile(string $name): ?DocumentProfile;





    /**
     * Get the default modex model profile name from configuration.
     *
     * @return string
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultModexModelProfileName(): string;

    /**
     * Import a specific modex model profile from configuration.
     *
     * @param string $name Profile name.
     * @return ModexModelProfile|null
     * @throws Exception If profile could not be built.
     */
    public static function buildModexModelProfile(string $name): ?ModexModelProfile;
}
