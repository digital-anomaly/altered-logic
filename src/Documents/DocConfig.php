<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents;

use DigitalAnomaly\AlteredLogic\Config\DocConfigStore;
use DigitalAnomaly\AlteredLogic\Registry\Registry;
use DigitalAnomaly\AlteredLogic\Support\Class\StaticEntryClassTrait;

/**
 * Manage document settings.
 *
 * @method static DocConfigStore debugLevel(int $debugLevel): DocConfigStore Set the global debug level for document use: 0 = off, 1 = basic, 2 = verbose.
 * @method static DocConfigStore deferBatchSize(int $deferBatchSize): DocConfigStore Set the number of items to include in a batch.
 *
 * AI instructions (static-entry-pattern): this is the "entry" class, which boots the `/src/Config/DocConfigStore.php` class.
 */
final class DocConfig
{
    use StaticEntryClassTrait;



    /**
     * Get the instance of the doc config store.
     *
     * @return DocConfigStore
     */
    private static function getInstance(): DocConfigStore
    {
        return Registry::docConfig();
    }
}
