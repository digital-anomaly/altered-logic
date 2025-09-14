<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal\Traits;

use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexCurrentState;

/**
 * Trait that provides methods to configure the state for a Modex request.
 */
trait HasModexStateTrait
{
    /** @var ModexCurrentState The ModexCurrent1State instance for the Modex request. */
    private ModexCurrentState $state;



    /**
     * Get the ModexCurrentState instance.
     *
     * @return ModexCurrentState
     */
    protected function getState(): ModexCurrentState
    {
        return $this->state ??= new ModexCurrentState();
    }

    /**
     * Reset the state - for use when continuing the conversation, so it starts with none defined.
     *
     * @return void
     */
    protected function resetState(): void
    {
        $this->state = new ModexCurrentState();
    }
}
