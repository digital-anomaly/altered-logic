<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Profiles;

use DigitalAnomaly\AlteredLogic\AlteredLogic;
use DigitalAnomaly\AlteredLogic\Exceptions\DocumentException;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocSearcherInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocStoreInterface;

/**
 * A "document profile", containing details about where documents should be stored, and which doc-searchers to use.
 */
final class DocumentProfile
{
    /** @var array<string,DocSearcherInterface> The doc-searchers to use. */
    private array $docSearchers = [];



    /**
     * Constructor.
     *
     * @param DocStoreInterface $docStore The document store to use.
     */
    public function __construct(
        private DocStoreInterface $docStore,
    ) {}





    /**
     * Get the document store.
     *
     * @return DocStoreInterface
     */
    public function getDocStore(): DocStoreInterface
    {
        return $this->docStore;
    }





    /**
     * Attach a DocSearcher to this profile.
     *
     * @param DocSearcherInterface $docSearcher The doc-searcher to use.
     * @param string               $name        The name to give this doc-searcher.
     * @return self
     */
    public function attachSearcher(DocSearcherInterface $docSearcher, string $name): self
    {
        // todo - checkthat this $docSearcher hasn't been used in any other DocumentProfiles
        $this->docSearchers[$name] = $docSearcher;

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
        return $this->docSearchers !== []
            ? $this->docSearchers
            : throw DocumentException::noDocSearchersHaveBeenAttached();
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
        return isset($this->docSearchers[$name])
            ? $this->docSearchers[$name]
            : throw DocumentException::docSearcherIsntAttached($name, \array_keys($this->docSearchers));
    }



    /**
     * Register the document profile with the AlteredLogic class.
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
