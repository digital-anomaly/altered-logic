<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Documents\Internal\DocSearchableGatedBatch;

use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocSearcherInterface;
use DigitalAnomaly\AlteredLogic\Profiles\DocumentProfile;

/**
 * Trait to add to DocSearchableGatedBatchInterface classes.
 *
 * @template TDocSearchableGatedBatchItem of GatedBatchItemWithDocSearchableInterface
 */
trait DocSearchableGatedBatchTrait
{
    /** @var DocumentProfile The document profile. */
    public private(set) DocumentProfile $documentProfile;

    /** @var DocSearcherInterface The doc searcher. */
    public private(set) DocSearcherInterface $docSearcher;

    /** @var integer The doc debug level. */
    public private(set) int $docDebugLevel;
}
