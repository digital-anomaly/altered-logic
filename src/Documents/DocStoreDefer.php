<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents;

use DigitalAnomaly\AlteredLogic\Documents\Builder\DocStore\AbstractDocStoreBuilder;
use DigitalAnomaly\AlteredLogic\Support\Framework\DependencyInjection;

/**
 * Class for the storage of documents and their search data, in a deferred manner.
 */
final class DocStoreDefer extends AbstractDocStoreBuilder
{
    /** @var boolean Whether the documents are being created in a deferred manner or not. */
    protected bool $isDeferred = true;





    /**
     * Create a new, unconfigured DocStoreDefer instance.
     *
     * When using a framework, its instantiated using the framework's dependency injection functionality.
     *
     * @return self
     */
    public static function new()
    {
        // Note: the return type is not specified in PHP.
        // This is so the framework can return a mock, intended to act like an DocStoreDefer instance

        /** @var self $instance */
        $instance = DependencyInjection::instantiate(self::class);

        return $instance;
    }





    /**
     * Flush doc-searchables - This processes all outstanding doc-searchables globally (across all models).
     *
     * @return self
     */
    public function flush(): self
    {
        $this->_flush();

        return $this;
    }
}
