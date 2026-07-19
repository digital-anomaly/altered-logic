<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatch\DTOs;

use DigitalAnomaly\AlteredLogic\Credentials\CredentialsOverride;
use DigitalAnomaly\AlteredLogic\Embed\EmbedFaker;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocSearcherInterface;
use DigitalAnomaly\AlteredLogic\Profiles\DocumentProfile;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedCacheProfile;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedModelProfile;

/**
 * Identifies which pooled DocSearchableGatedBatch a piece of document work belongs to.
 */
final readonly class DocSearchableGatedBatchIdentityDTO
{
    /**
     * Constructor.
     *
     * @param DocumentProfile          $documentProfile     The document profile to use.
     * @param DocSearcherInterface     $docSearcher         The doc-searcher to store searchables with.
     * @param EmbedModelProfile|null   $embedModelProfile   The model profile to use.
     * @param EmbedCacheProfile|null   $embedCacheProfile   The cache profile to use.
     * @param EmbedFaker|null          $embedFaker          The faker to use when generating embeddings.
     * @param CredentialsOverride|null $credentialsOverride The credentials to use instead of each model's own.
     * @param integer                  $docDebugLevel       The doc debug level to use.
     * @param integer                  $embedDebugLevel     The embed debug level to use.
     */
    public function __construct(
        public DocumentProfile $documentProfile,
        public DocSearcherInterface $docSearcher,
        public ?EmbedModelProfile $embedModelProfile,
        public ?EmbedCacheProfile $embedCacheProfile,
        public ?EmbedFaker $embedFaker,
        public ?CredentialsOverride $credentialsOverride,
        public int $docDebugLevel,
        public int $embedDebugLevel,
    ) {}

    /**
     * Build the key used to pool batches with this identity.
     *
     * @return string
     */
    public function buildKey(): string
    {
        $documentProfileKey = \spl_object_id($this->documentProfile);

        $docSearcherKey = \spl_object_id($this->docSearcher);

        $embedModelProfileKey = \is_object($this->embedModelProfile)
            ? \spl_object_id($this->embedModelProfile)
            : '';

        $embedCacheProfileKey = \is_object($this->embedCacheProfile)
            ? \spl_object_id($this->embedCacheProfile)
            : '';

        $fakerKey = \is_object($this->embedFaker)
            ? \spl_object_id($this->embedFaker)
            : '';

        // value-based (not spl_object_id) so identical overrides pool together and different overrides never merge
        $credentialsOverrideKey = $this->credentialsOverride?->fingerprint() ?? '';

        return "{$documentProfileKey}"
            . ":{$docSearcherKey}"
            . ":{$embedModelProfileKey}"
            . ":{$embedCacheProfileKey}"
            . ":{$fakerKey}"
            . ":{$credentialsOverrideKey}"
            . ":{$this->docDebugLevel}"
            . ":{$this->embedDebugLevel}";
    }
}
