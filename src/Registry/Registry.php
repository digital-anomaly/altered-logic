<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Registry;

use DigitalAnomaly\AlteredLogic\Config\DocConfigStore;
use DigitalAnomaly\AlteredLogic\Config\EmbedConfigStore;
use DigitalAnomaly\AlteredLogic\Config\GeneralConfigStore;
use DigitalAnomaly\AlteredLogic\Config\ModexConfigStore;
use DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatch;
use DigitalAnomaly\AlteredLogic\Embed\EmbedFaker;
use DigitalAnomaly\AlteredLogic\Embed\Internal\EmbedGatedBatch;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocSearcherInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Providers\CredentialsInterface;
use DigitalAnomaly\AlteredLogic\Profiles\DocumentProfile;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedCacheProfile;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedModelProfile;
use DigitalAnomaly\AlteredLogic\Profiles\ModexModelProfile;
use DigitalAnomaly\AlteredLogic\Support\Class\SingletonHelper;

/**
 * Centralised registry for various classes.
 */
final class Registry
{
    /** @var GeneralConfigStore The general settings. */
    private GeneralConfigStore $generalConfig;

    /** @var EmbedConfigStore The embed settings. */
    private EmbedConfigStore $embedConfig;

    /** @var DocConfigStore The document settings. */
    private DocConfigStore $docConfig;

    /** @var ModexConfigStore The modex settings. */
    private ModexConfigStore $modexConfig;



    /** @var CredentialsRegistryGroup The available provider credentials. */
    private CredentialsRegistryGroup $credentials;

    /** @var EmbedModelProfileRegistryGroup The available embed model profiles. */
    private EmbedModelProfileRegistryGroup $embedModelProfiles;

    /** @var EmbedCacheProfileRegistryGroup The available embed cache profiles. */
    private EmbedCacheProfileRegistryGroup $embedCacheProfiles;

    /** @var DocumentProfileRegistryGroup The available document profiles. */
    private DocumentProfileRegistryGroup $documentProfiles;

    /** @var ModexModelProfileRegistryGroup The available modex model profiles. */
    private ModexModelProfileRegistryGroup $modexModelProfiles;



    /** @var array<string,EmbedGatedBatch> The available embed gated batches. */
    private array $embedGatedBatches = [];

    /** @var array<string,EmbedGatedBatch> The available deferred embed gated batches. */
    private array $embedGatedBatchesDeferred = [];



    /** @var array<string,DocSearchableGatedBatch> The available DocSearchable gated batches. */
    private array $docSearchableGatedBatches = [];

    /** @var array<string,DocSearchableGatedBatch> The available deferred DocSearchable gated batches. */
    private array $docSearchableGatedBatchesDeferred = [];



    /**
     * Initialise the registry.
     *
     * @return void
     */
    private function __construct()
    {
        $this->generalConfig = new GeneralConfigStore();
        $this->embedConfig = new EmbedConfigStore();
        $this->docConfig = new DocConfigStore();
        $this->modexConfig = new ModexConfigStore();

        $this->credentials = new CredentialsRegistryGroup();
        $this->embedModelProfiles = new EmbedModelProfileRegistryGroup();
        $this->embedCacheProfiles = new EmbedCacheProfileRegistryGroup();
        $this->documentProfiles = new DocumentProfileRegistryGroup();
        $this->modexModelProfiles = new ModexModelProfileRegistryGroup();
    }





    /**
     * Get the singleton instance of this class.
     *
     * @return self
     */
    private static function instance(): self
    {
        return SingletonHelper::instance(self::class, fn() => new self());
    }





    /**
     * Get the general config.
     *
     * @return GeneralConfigStore
     */
    public static function generalConfig(): GeneralConfigStore
    {
        return self::instance()->generalConfig;
    }

    /**
     * Get the embed config store.
     *
     * @return EmbedConfigStore
     */
    public static function embedConfig(): EmbedConfigStore
    {
        return self::instance()->embedConfig;
    }

    /**
     * Get the document config store.
     *
     * @return DocConfigStore
     */
    public static function docConfig(): DocConfigStore
    {
        return self::instance()->docConfig;
    }

    /**
     * Get the modex config store.
     *
     * @return ModexConfigStore
     */
    public static function modexConfig(): ModexConfigStore
    {
        return self::instance()->modexConfig;
    }

    /**
     * Get the provider credentials group.
     *
     * @return AbstractRegistryGroup<CredentialsInterface>
     */
    public static function credentials(): AbstractRegistryGroup
    {
        return self::instance()->credentials;
    }

    /**
     * Get the document profiles group.
     *
     * @return AbstractRegistryGroup<DocumentProfile>
     */
    public static function documentProfiles(): AbstractRegistryGroup
    {
        return self::instance()->documentProfiles;
    }

    /**
     * Get the embed model profiles group.
     *
     * @return AbstractRegistryGroup<EmbedModelProfile>
     */
    public static function embedModelProfiles(): AbstractRegistryGroup
    {
        return self::instance()->embedModelProfiles;
    }

    /**
     * Get the embed cache profiles group.
     *
     * @return AbstractRegistryGroup<EmbedCacheProfile>
     */
    public static function embedCacheProfiles(): AbstractRegistryGroup
    {
        return self::instance()->embedCacheProfiles;
    }

    /**
     * Get the modex model profiles group.
     *
     * @return AbstractRegistryGroup<ModexModelProfile>
     */
    public static function modexModelProfiles(): AbstractRegistryGroup
    {
        return self::instance()->modexModelProfiles;
    }





    /**
     * Get the relevant embed gated batch.
     *
     * A new one will be created for each combination of the inputs.
     *
     * @param boolean                $isDeferred        Whether the batch is deferred or not.
     * @param EmbedModelProfile      $embedModelProfile The embed model profile to get the gated batch for.
     * @param EmbedCacheProfile|null $embedCacheProfile The embed cache profile to get the gated batch for.
     * @param EmbedFaker|null        $embedFaker        The faker to use for the batch.
     * @param integer                $debugLevel        The debug level to use for the batch.
     * @return EmbedGatedBatch
     */
    public static function getEmbedGatedBatch(
        bool $isDeferred,
        EmbedModelProfile $embedModelProfile,
        ?EmbedCacheProfile $embedCacheProfile,
        ?EmbedFaker $embedFaker,
        int $debugLevel,
    ): EmbedGatedBatch {

        $key = self::buildEmbedGatedBatchKey(
            $embedModelProfile,
            $embedCacheProfile,
            $embedFaker,
            $debugLevel,
        );

        $new = fn(): EmbedGatedBatch => new EmbedGatedBatch(
            $embedModelProfile,
            $embedCacheProfile,
            $embedFaker,
            $debugLevel,
        );

        return $isDeferred
            ? self::instance()->embedGatedBatchesDeferred[$key] ??= $new()
            : self::instance()->embedGatedBatches[$key] ??= $new();
    }

    /**
     * Build the key for the embed gated batch.
     *
     * @param EmbedModelProfile      $embedModelProfile The model profile to get the key for.
     * @param EmbedCacheProfile|null $embedCacheProfile The cache profile to get the key for.
     * @param EmbedFaker|null        $embedFaker        The faker to get the key for.
     * @param integer                $debugLevel        The debug level to get the key for.
     * @return string
     */
    private static function buildEmbedGatedBatchKey(
        EmbedModelProfile $embedModelProfile,
        ?EmbedCacheProfile $embedCacheProfile,
        ?EmbedFaker $embedFaker,
        int $debugLevel,
    ): string {

        $embedModelProfileKey = \spl_object_id($embedModelProfile);

        $embedCacheProfileKey = \is_object($embedCacheProfile)
            ? \spl_object_id($embedCacheProfile)
            : '';

        $fakerKey = \is_object($embedFaker)
            ? \spl_object_id($embedFaker)
            : '';

        return "{$embedModelProfileKey}:{$embedCacheProfileKey}:{$fakerKey}:{$debugLevel}";
    }

    /**
     * Get all the deferred embed gated batches.
     *
     * @return array<string,EmbedGatedBatch>
     */
    public static function getAllDeferredEmbedGatedBatches(): array
    {
        return self::instance()->embedGatedBatchesDeferred;
    }





    /**
     * Get the relevant doc searchable gated batch.
     *
     * A new one will be created for each combination of the inputs.
     *
     * @param boolean                $isDeferred        Whether the batch is deferred or not.
     * @param DocumentProfile        $documentProfile   The document profile to get the gated batch for.
     * @param DocSearcherInterface   $docSearcher       The doc-searcher to get the gated batch for.
     * @param EmbedModelProfile|null $embedModelProfile The embed model profile to get the gated batch for.
     * @param EmbedCacheProfile|null $embedCacheProfile The embed cache profile to get the gated batch for.
     * @param EmbedFaker|null        $embedFaker        The faker to use for the batch.
     * @param integer                $docDebugLevel     The doc debug level to use for the batch.
     * @param integer                $embedDebugLevel   The embed debug level to use for the batch.
     * @return DocSearchableGatedBatch
     */
    public static function getDocSearchableGatedBatch(
        bool $isDeferred,
        DocumentProfile $documentProfile,
        DocSearcherInterface $docSearcher,
        ?EmbedModelProfile $embedModelProfile,
        ?EmbedCacheProfile $embedCacheProfile,
        ?EmbedFaker $embedFaker,
        int $docDebugLevel,
        int $embedDebugLevel,
    ): DocSearchableGatedBatch {

        $key = self::buildDocSearchableGatedBatchKey(
            $documentProfile,
            $docSearcher,
            $embedModelProfile,
            $embedCacheProfile,
            $embedFaker,
            $docDebugLevel,
            $embedDebugLevel,
        );

        $new = fn(): DocSearchableGatedBatch => new DocSearchableGatedBatch(
            $documentProfile,
            $docSearcher,
            $embedModelProfile,
            $embedCacheProfile,
            $embedFaker,
            $docDebugLevel,
            $embedDebugLevel,
        );

        return $isDeferred
            ? self::instance()->docSearchableGatedBatchesDeferred[$key] ??= $new()
            : self::instance()->docSearchableGatedBatches[$key] ??= $new();
    }

    /**
     * Build the key for the doc searchable gated batch.
     *
     * @param DocumentProfile        $documentProfile   The document profile to get the gated batch for.
     * @param DocSearcherInterface   $docSearcher       The doc-searcher to get the gated batch for.
     * @param EmbedModelProfile|null $embedModelProfile The embed model profile to get the gated batch for.
     * @param EmbedCacheProfile|null $embedCacheProfile The embed cache profile to get the gated batch for.
     * @param EmbedFaker|null        $embedFaker        The faker to use for the batch.
     * @param integer                $docDebugLevel     The doc debug level to use for the batch.
     * @param integer                $embedDebugLevel   The embed debug level to use for the batch.
     * @return string
     */
    private static function buildDocSearchableGatedBatchKey(
        DocumentProfile $documentProfile,
        DocSearcherInterface $docSearcher,
        ?EmbedModelProfile $embedModelProfile,
        ?EmbedCacheProfile $embedCacheProfile,
        ?EmbedFaker $embedFaker,
        int $docDebugLevel,
        int $embedDebugLevel,
    ): string {

        $documentProfileKey = \spl_object_id($documentProfile);

        $docSearcherKey = \spl_object_id($docSearcher);

        $embedModelProfileKey = \is_object($embedModelProfile)
            ? \spl_object_id($embedModelProfile)
            : '';

        $embedCacheProfileKey = \is_object($embedCacheProfile)
            ? \spl_object_id($embedCacheProfile)
            : '';

        $fakerKey = \is_object($embedFaker)
            ? \spl_object_id($embedFaker)
            : '';

        return "{$documentProfileKey}"
            . ":{$docSearcherKey}"
            . ":{$embedModelProfileKey}"
            . ":{$embedCacheProfileKey}"
            . ":{$fakerKey}"
            . ":{$docDebugLevel}"
            . ":{$embedDebugLevel}";
    }

    /**
     * Get all the deferred doc searchable gated batches.
     *
     * @return array<string,DocSearchableGatedBatch>
     */
    public static function getAllDeferredDocSearchableGatedBatches(): array
    {
        return self::instance()->docSearchableGatedBatchesDeferred;
    }
}
