<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal\Traits;

use CodeDistortion\Backoff\Backoff;

/**
 * Trait that provides Backoff functionality to use when interacting with the AI provider.
 *
 * @see https://github.com/code-distortion/backoff
 */
trait HasBackoffTrait
{
    /** @var Backoff|null The backoff strategy to use. */
    private ?Backoff $backoff = null;



    /**
     * Specify the backoff strategy to use when interacting with the AI provider.
     *
     * @see https://github.com/code-distortion/backoff
     *
     * @param Backoff|null $backoff The backoff strategy to use.
     * @return self
     */
    public function backoff(?Backoff $backoff): self
    {
        $this->backoff = $backoff;

        return $this;
    }

    /**
     * Get the backoff strategy to use when interacting with the AI provider.
     *
     * @return Backoff|null
     */
    protected function getBackoff(): ?Backoff
    {
        return $this->backoff;
    }
}
