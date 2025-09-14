<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch;

use DigitalAnomaly\AlteredLogic\Embed\Vector;

/**
 * Represents an embedding that is yet to be resolved.
 */
final class PendingEmbedding
{
    /**
     * Constructor.
     *
     * @param string      $source The embedding source.
     * @param Vector|null $vector The embedding vector, once it's been resolved.
     */
    public function __construct(
        public private(set) string $source,
        public private(set) ?Vector $vector = null,
    ) {}

    /**
     * Set the embedding vector.
     *
     * @param Vector|null $vector The embedding vector.
     * @return void
     */
    public function setVector(?Vector $vector): void
    {
        $this->vector = $vector;
    }
}
