<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents\Builder\DocStore;

/**
 * Builder for the creation and storage of documents.
 *
 * AI instructions (Static Entry Pattern): This is the "enterable" class, "entered" by the DocStore class.
 */
final class DocStoreBuilder extends AbstractDocStoreBuilder
{
    /** @var boolean Whether the documents are being created in a deferred manner or not. */
    protected bool $isDeferred = false;
}
