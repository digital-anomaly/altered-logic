<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents\Builder\DocStore;

/**
 * Builder for the creation and storage of documents, in a deferred manner.
 *
 * AI instructions (Static Entry Pattern): This is the "enterable" class, "entered" by the DocStoreDefer class.
 */
final class DocStoreDeferBuilder extends AbstractDocStoreBuilder
{
    /** @var boolean Whether the documents are being created in a deferred manner or not. */
    protected bool $isDeferred = true;



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
