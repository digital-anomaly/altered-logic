<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents;

use DigitalAnomaly\AlteredLogic\Documents\Builder\DocStore\AbstractDocStoreBuilder;
use DigitalAnomaly\AlteredLogic\Support\Framework\DependencyInjection;

/**
 * Class for the storage of documents and their search data.
 */
final class DocStore extends AbstractDocStoreBuilder
{
    /** @var boolean Whether the documents are being created in a deferred manner or not. */
    protected bool $isDeferred = false;





    /**
     * Create a new, unconfigured DocStore instance.
     *
     * When using a framework, its instantiated using the framework's dependency injection functionality.
     *
     * @return self
     */
    public static function new()
    {
        // Note: the return type is not specified in PHP.
        // This is so the framework can return a mock, intended to act like an DocStore instance

        /** @var self $instance */
        $instance = DependencyInjection::instantiate(self::class);

        return $instance;
    }
}
