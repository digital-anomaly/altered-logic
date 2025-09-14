<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex;

use DigitalAnomaly\AlteredLogic\Config\ModexConfigStore;
use DigitalAnomaly\AlteredLogic\Registry\Registry;
use DigitalAnomaly\AlteredLogic\Support\Class\StaticEntryClassTrait;

/**
 * Manage modex settings.
 *
 * @method static ModexConfigStore debugLevel(int $debugLevel): ModexConfigStore Set the global debug level for modex use: 0 = off, 1 = basic, 2 = verbose.
 *
 * AI instructions (static-entry-pattern): this is the "entry" class, which boots the `/src/Config/ModexConfigStore.php` class.
 */
final class ModexConfig
{
    use StaticEntryClassTrait;



    /**
     * Get the instance of the modex config store.
     *
     * @return ModexConfigStore
     */
    private static function getInstance(): ModexConfigStore
    {
        return Registry::modexConfig();
    }
}
