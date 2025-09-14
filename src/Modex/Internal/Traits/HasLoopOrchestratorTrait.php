<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal\Traits;

use DigitalAnomaly\AlteredLogic\Exceptions\ModexException;
use DigitalAnomaly\AlteredLogic\Modex\Modex;
use DigitalAnomaly\AlteredLogic\Modex\ModexControl;
use DigitalAnomaly\AlteredLogic\Support\Class\CallableInspector;
use DigitalAnomaly\AlteredLogic\Support\Framework\DependencyInjection;

/**
 * Trait that provides loop orchestrator functionality, which is used to plan how to interact with the AI provider.
 *
 * @mixin Modex
 */
trait HasLoopOrchestratorTrait
{
    /** @var callable|null The loop orchestrator to use. */
    private $loopOrchestrator = null;

    /** @var boolean Whether the loop orchestrator operates in workflow mode or not. */
    private bool $loopOrchestratorWorkflowMode = false;



    /**
     * Specify the loop orchestrator to use when planning how to interact with the AI provider.
     *
     * @param callable|object|array{0:class-string|object,1:string}|class-string|string $loopOrchestrator The loop
     *                                                                                                    orchestrator
     *                                                                                                    to use.
     * @return self
     */
    public function prepareloop(callable|object|array|string $loopOrchestrator): self
    {
        $this->throwIfInitialisationMethodsWereCalled($this->calledInitialisationMethods);

        $this->loopOrchestrator = CallableInspector::newloopOrchestrator($loopOrchestrator)->deriveCallable();

        return $this;
    }

    /**
     * Throw an exception if the definer called methods that would be reset before calling the loop orchestrator.
     *
     * @param array $methods The relevant methods that were called.
     * @return void
     */
    private function throwIfInitialisationMethodsWereCalled(array $methods): void
    {
        if (\count($methods) === 0) {
            return;
        }

        throw ModexException::disallowedInitMethodsCalledWhilePrepareLoopMethodExists(
            \array_keys($methods),
        );
    }

    /**
     * Specify the loop orchestrator to use in workflow mode.
     *
     * @param callable|object|array{0:class-string|object,1:string}|class-string|string $workflow The workflow to use.
     * @return self
     */
    public function workflow(callable|object|array|string $workflow): self
    {
        if (!\is_callable($workflow)) {
            throw new \Exception('Invalid Workflow callback (make sure it exists and is publically accessible)'); // todo - throw a custom exception
        }

        $this->loopOrchestrator = $workflow;

        $this->loopOrchestratorWorkflowMode = true;

        return $this;
    }

    /**
     * Check if the loop orchestrator has been set.
     *
     * @return boolean
     */
    private function hasLoopOrchestrator(): bool
    {
        return $this->loopOrchestrator !== null;
    }

    /**
     * Call the loop orchestrator and return the result it gives.
     *
     * It is called using dependency injection, and the following parameters are passed:
     * - Modex::class => the Modex instance
     * - 'modex' => the Modex instance
     *
     * @param mixed $response The response from the most recent ModexTxn.
     * @return ModexControl|null
     */
    private function callLoopOrchestrator(mixed $response): ?ModexControl
    {
        if ($this->loopOrchestrator === null) {
            return null;
        }



        // todo - run this in a try-catch block ?
        $return = DependencyInjection::call(
            $this->loopOrchestrator,
            [Modex::class => $this],
            ['modex' => $this],
        );



        // when in workflow mode, return the return value wrapped in a ModexControl instance
        if ($this->loopOrchestratorWorkflowMode) {
            return ModexControl::return($return);
        }

        // no control specified, so continue
        if ($return === null) {
            return null;
        }

        // a ModexControl instance was returned, so return it
        if ($return instanceof ModexControl) {
            return $return;
        }

        // todo - add ability for the loop orchestrator to return a new Modex? i.e. new Modex to start a different conversation?

        // todo - add extra description about what can be returned when it's built (e.g. Modex::stop())
        throw new \Exception('The loop orchestrator must return a ModexControl instance, null, or nothing at all'); // todo - add a custom exception
    }
}
