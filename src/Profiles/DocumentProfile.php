<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Profiles;

use DigitalAnomaly\AlteredLogic\AlteredLogic;
use DigitalAnomaly\AlteredLogic\Exceptions\DocumentException;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocSearcherInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocStoreInterface;
use DigitalAnomaly\AlteredLogic\Registry\Registry;

/**
 * A "document profile", containing details about where documents should be stored, and which doc-searchers to use.
 */
final class DocumentProfile
{
    /** @var string|null The name of the doc-store to use. */
    private ?string $docStoreName = null;

    /** @var array<string,string> The doc-searcher names to use. */
    private array $docSearcherNames = [];



    /**
     * Constructor.
     */
    public function __construct()
    {
    }





    /**
     * Set the doc-store to use.
     *
     * @param string $registeredDocStoreName The name of the registered doc-store.
     * @return self
     */
    public function setStore(string $registeredDocStoreName): self
    {
        $this->docStoreName = $registeredDocStoreName;

        return $this;
    }


    /**
     * Get the document store.
     *
     * @return DocStoreInterface
     * @throws DocumentException When the doc-store hasn't been set or cannot be resolved.
     */
    public function getDocStore(): DocStoreInterface
    {
        if ($this->docStoreName === null) {
            throw DocumentException::docStoreNotSet();
        }

        return Registry::docStores()->getOrThrow($this->docStoreName);
    }





    /**
     * Attach a DocSearcher to this profile.
     *
     * @param string $registeredDocSearcherName The name of the registered doc-searcher.
     * @return self
     */
    public function attachSearcher(string $registeredDocSearcherName): self
    {
        $this->docSearcherNames[$registeredDocSearcherName] = $registeredDocSearcherName;

        return $this;
    }

    /**
     * Get all of the attached DocSearchers.
     *
     * @return array<string,DocSearcherInterface>
     * @throws DocumentException When no doc-searchers have been defined.
     */
    public function getDocSearchers(): array
    {
        if ($this->docSearcherNames === []) {
            throw DocumentException::noDocSearchersHaveBeenAttached();
        }

        $docSearchers = [];
        foreach ($this->docSearcherNames as $name) {
            $docSearchers[$name] = Registry::docSearchers()->getOrThrow($name);
        }

        return $docSearchers;
    }

    /**
     * Get a particular attached DocSearcher.
     *
     * @param string $name The name of the doc-searcher to get.
     * @return DocSearcherInterface
     * @throws DocumentException When the doc-searcher doesn't exist.
     */
    public function getDocSearcher(string $name): DocSearcherInterface
    {
        if (!isset($this->docSearcherNames[$name])) {
            throw DocumentException::docSearcherIsntAttached($name, \array_keys($this->docSearcherNames));
        }

        return Registry::docSearchers()->getOrThrow($name);
    }



    /**
     * Register this document profile.
     *
     * @param string  $name      The name of the profile to register.
     * @param boolean $isDefault Whether this is the default profile or not (the first one is default unless another is
     *                           specified).
     * @return void
     */
    public function register(string $name, bool $isDefault = false): void
    {
        AlteredLogic::registerDocumentProfile($name, $this, $isDefault);
    }
}
