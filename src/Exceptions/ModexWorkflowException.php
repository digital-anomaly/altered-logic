<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Exceptions;

/**
 * Exceptions related to ModexWorkflows.
 */
final class ModexWorkflowException extends ModexException
{
    /**
     * Thrown when a ModexWorkflow is missing its workflow() method.
     *
     * @return self
     */
    public static function missingWorkflowMethod(): self
    {
        return new self('A ModexWorkflow must have a workflow() method');
    }

    /**
     * Thrown when a ModexWorkflow is run more than once.
     *
     * @return self
     */
    public static function workflowAlreadyRun(): self
    {
        return new self('A ModexWorkflow can only be run once');
    }
}
