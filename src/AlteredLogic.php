<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic;

use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
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
use DigitalAnomaly\AlteredLogic\Registry\Registry;
use DigitalAnomaly\AlteredLogic\Support\ValueStore;

/**
 * Manages settings for the AlteredLogic library.
 */
final class AlteredLogic
{
    /**
     * Register provider credentials.
     *
     * @param string|AiProvidersEnum $name        The provider's name.
     * @param CredentialsInterface   $credentials The provider credentials to register.
     * @return void
     */
    public static function registerCredentials(string|AiProvidersEnum $name, CredentialsInterface $credentials): void
    {
        Registry::credentials()->register($name, $credentials);
    }

    /**
     * Register an embed model profile.
     *
     * @param string            $name         The profile's name.
     * @param EmbedModelProfile $modelProfile The profile to register.
     * @param boolean           $isDefault    Whether this is the default profile or not.
     * @return void
     */
    public static function registerEmbedModelProfile(
        string $name,
        EmbedModelProfile $modelProfile,
        bool $isDefault = false,
    ): void {

        Registry::embedModelProfiles()->register($name, $modelProfile, $isDefault);
    }

    /**
     * Register an embed model.
     *
     * @param string              $name      The name to register the model under.
     * @param EmbedModelInterface $model     The model to register.
     * @param boolean             $isDefault Whether this is the default model.
     * @return void
     */
    public static function registerEmbedModel(
        string $name,
        EmbedModelInterface $model,
        bool $isDefault = false,
    ): void {

        Registry::embedModels()->register($name, $model, $isDefault);
    }

    /**
     * Register an embed cache profile.
     *
     * @param string            $name         The profile's name.
     * @param EmbedCacheProfile $cacheProfile The profile to register.
     * @param boolean           $isDefault    Whether this is the default profile or not.
     * @return void
     */
    public static function registerEmbedCacheProfile(
        string $name,
        EmbedCacheProfile $cacheProfile,
        bool $isDefault = false,
    ): void {

        Registry::embedCacheProfiles()->register($name, $cacheProfile, $isDefault);
    }

    /**
     * Register an embed cache.
     *
     * @param string              $name      The name to register the cache under.
     * @param EmbedCacheInterface $cache     The cache to register.
     * @param boolean             $isDefault Whether this is the default cache.
     * @return void
     */
    public static function registerEmbedCache(
        string $name,
        EmbedCacheInterface $cache,
        bool $isDefault = false,
    ): void {

        Registry::embedCaches()->register($name, $cache, $isDefault);
    }

    /**
     * Register a document profile.
     *
     * @param string          $name            The profile's name.
     * @param DocumentProfile $documentProfile The profile to register.
     * @param boolean         $isDefault       Whether this is the default profile or not.
     * @return void
     */
    public static function registerDocumentProfile(
        string $name,
        DocumentProfile $documentProfile,
        bool $isDefault = false,
    ): void {

        Registry::documentProfiles()->register($name, $documentProfile, $isDefault);
    }

    /**
     * Register a doc-store.
     *
     * @param string            $name      The name to register the doc-store under.
     * @param DocStoreInterface $docStore  The doc-store to register.
     * @param boolean           $isDefault Whether this is the default doc-store (not currently used).
     * @return void
     */
    public static function registerDocStore(
        string $name,
        DocStoreInterface $docStore,
        bool $isDefault = false,
    ): void {

        Registry::docStores()->register($name, $docStore, $isDefault);
    }

    /**
     * Register a doc-searcher.
     *
     * @param string               $name        The name to register the doc-searcher under.
     * @param DocSearcherInterface $docSearcher The doc-searcher to register.
     * @param boolean              $isDefault   Whether this is the default doc-searcher (not currently used).
     * @return void
     */
    public static function registerDocSearcher(
        string $name,
        DocSearcherInterface $docSearcher,
        bool $isDefault = false,
    ): void {

        Registry::docSearchers()->register($name, $docSearcher, $isDefault);
    }

    /**
     * Register a modex model profile.
     *
     * @param string            $name         The profile's name.
     * @param ModexModelProfile $modelProfile The profile to register.
     * @param boolean           $isDefault    Whether this is the default profile or not.
     * @return void
     */
    public static function registerModexModelProfile(
        string $name,
        ModexModelProfile $modelProfile,
        bool $isDefault = false,
    ): void {

        Registry::modexModelProfiles()->register($name, $modelProfile, $isDefault);
    }

    /**
     * Register a modex model.
     *
     * @param string              $name      The name to register the model under.
     * @param ModexModelInterface $model     The model to register.
     * @param boolean             $isDefault Whether this is the default model.
     * @return void
     */
    public static function registerModexModel(
        string $name,
        ModexModelInterface $model,
        bool $isDefault = false,
    ): void {

        Registry::modexModels()->register($name, $model, $isDefault);
    }



    /**
     * Block HTTP requests.
     *
     * @param boolean $block Whether to block requests or not.
     * @return void
     */
    public static function blockAllRequests(bool $block = true): void
    {
        Registry::generalConfig()->blockRequests($block);
    }

    /**
     * Block embedding requests.
     *
     * @param boolean $block Whether to block requests or not.
     * @return void
     */
    public static function blockEmbeddingRequests(bool $block = true): void
    {
        Registry::embedConfig()->blockRequests($block);
    }

    /**
     * Block modex requests.
     *
     * @param boolean $block Whether to block requests or not.
     * @return void
     */
    public static function blockModexRequests(bool $block = true): void
    {
        Registry::modexConfig()->blockRequests($block);
    }



    /**
     * Remove all settings stored with the framework.
     *
     * Normally, settings will be forgotten at the end of the request lifecycle by the framework automatically.
     *
     * This method can be used if you're not using a supported framework and need the same functionality.
     *
     * @return void
     */
    public static function cleanUp(): void
    {
        ValueStore::cleanUp();
    }
}
