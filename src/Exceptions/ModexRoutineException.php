<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Exceptions;

use Throwable;

/**
 * Exceptions related to ModexRoutines.
 */
final class ModexRoutineException extends ModexException
{
    /**
     * Thrown when trying to share one ModexState between ModexRoutines, and the types are mismatched.
     *
     * @param string    $thisStateClass The class of the current state.
     * @param string    $newStateClass  The class of the new state.
     * @param Throwable $previous       The previous exception.
     * @return self
     */
    public static function mismatchedModexStateTypes(
        string $thisStateClass,
        string $newStateClass,
        Throwable $previous,
    ): self {

        return new self(
            "When sharing ModexState instances between ModexRoutine objects, the types need to be the same. "
                . "The current state {$thisStateClass} is being overridden by {$newStateClass}",
            previous: $previous,
        );
    }



    /**
     * Thrown when a ModexRoutine is missing its initialise() and prepareLoop() method.
     *
     * @return self
     */
    public static function missingInitialiseAndPrepareLoopMethods(): self
    {
        return new self('A ModexRoutine must have an initialise() and / or prepareLoop() method');
    }



    /**
     * Thrown when a ModexRoutine is run more than once.
     *
     * @return self
     */
    public static function routineAlreadyRun(): self
    {
        return new self('A ModexRoutine can only be run once');
    }
}
