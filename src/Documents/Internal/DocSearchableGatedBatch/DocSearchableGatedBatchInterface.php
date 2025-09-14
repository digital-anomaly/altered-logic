<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatch;

use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocSearcherInterface;
use DigitalAnomaly\AlteredLogic\Profiles\DocumentProfile;

/**
 * An interface for gated-batch of doc-searchable classes.
 */
interface DocSearchableGatedBatchInterface
{
    /** @var DocumentProfile The document profile. */
    public DocumentProfile $documentProfile { get; } // @phpcs:ignore

    /** @var DocSearcherInterface The doc searcher. */
    public DocSearcherInterface $docSearcher { get; } // @phpcs:ignore

    /** @var integer The doc debug level. */
    public int $docDebugLevel { get; } // @phpcs:ignore
}
