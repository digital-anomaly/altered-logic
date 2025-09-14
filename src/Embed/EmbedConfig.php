<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed;

use DigitalAnomaly\AlteredLogic\Config\EmbedConfigStore;
use DigitalAnomaly\AlteredLogic\Embed\EmbedFaker;
use DigitalAnomaly\AlteredLogic\Registry\Registry;
use DigitalAnomaly\AlteredLogic\Support\Class\StaticEntryClassTrait;

/**
 * Manage embed settings.
 *
 * @method static EmbedConfigStore debugLevel(int $debugLevel): EmbedConfigStore Set the global debug level for embed use: 0 = off, 1 = basic, 2 = verbose.
 * @method static EmbedConfigStore deferBatchSize(int $deferBatchSize): EmbedConfigStore Set the number of items to include in a batch.
 * @method static EmbedConfigStore faker(EmbedFaker|null $faker): EmbedConfigStore Set the faker to use when generating embeddings.
 *
 * AI instructions (static-entry-pattern): this is the "entry" class, which boots the `/src/Config/EmbedConfigStore.php` class.
 */
final class EmbedConfig
{
    use StaticEntryClassTrait;



    /**
     * Get the instance of the embed config store.
     *
     * @return EmbedConfigStore
     */
    private static function getInstance(): EmbedConfigStore
    {
        return Registry::embedConfig();
    }
}
