<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex;

use DigitalAnomaly\AlteredLogic\Exceptions\ModexRoutineException;
use DigitalAnomaly\AlteredLogic\Exceptions\ModexWorkflowException;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\ModexStateInterface;
use DigitalAnomaly\AlteredLogic\Support\Framework\DependencyInjection;
use TypeError;

/**
 * A structured Modex workflow.
 *
 * @property ModexStateInterface $state The routine's storage location for state.
 * @method self run(mixed ...$args): static
 */
abstract class ModexWorkflow
{
    /** @var Modex The modex that handles the workflow. */
    private Modex $modex;

    /** @var ModexState The workflow's storage location for state. */
    private ModexState $_state {
        get => $this->_state ??= new ModexState();
    }

    /** @var boolean Whether the workflow has been run yet or not. */
    private bool $modexWorkflowHasRun = false;





    /**
     * Magic method to allow the child class to override the state property with their own type.
     *
     * @param string $name The name of the property.
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        if ($name === 'state') {
            if (\property_exists($this, $name)) {
                return $this->state;
            }
            return $this->_state;
        }

        throw new \Exception("Property \"{$name}\" not found in " . \get_class($this));
    }

    /**
     * Magic method to allow the child class to override the run() method with their own signature.
     *
     * @param string  $name      The name of the method.
     * @param mixed[] $arguments The arguments to pass to the method.
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        $name = \mb_strtolower($name);

        // run method - is present here inside __call() so the child class can override it with their own signature
        if ($name === 'run') {
            return $this->_run(...$arguments);
        }

        throw new \Exception("Method \"{$name}\" not found in " . \get_class($this));
    }





    /**
     * Create a new workflow instance.
     *
     * When using a framework, its instantiated using the framework's dependency injection functionality.
     *
     * @return static
     */
    public static function new()
    {
        // Note: the return type is not specified in PHP.
        // This is so the framework can return a mock, intended to act like a ModexWorkflow instance

        /** @var static $instance */
        $instance = DependencyInjection::instantiate(static::class);

        return $instance;
    }



    /**
     * Run the AI workflow - add this method to your child class to use.
     *
     * ->workflow() is called once, and its return value is made available to the caller via ->result.
     *
     * @return mixed
     */
    // protected function workflow()
    // {
    //     // … to be overridden by child classes
    // }



    /**
     * Share the state of another process with this one.
     *
     * @param ModexStateInterface $state The state to share.
     * @return static
     * @throws ModexRoutineException If the state types are mismatched.
     */
    final public function shareState(ModexStateInterface $state): static
    {
        try {

            $this->state = $state;

        } catch (TypeError $e) {

            $thisStateClass = \get_class($this->state);
            $newStateClass = \get_class($state);

            throw ModexRoutineException::mismatchedModexStateTypes($thisStateClass, $newStateClass, $e);
        }

        return $this;
    }



    /**
     * Run the workflow.
     *
     * The result can be collected afterwards using ->result.
     *
     * @param mixed ...$args Arguments to pass to the workflow (if any).
     * @return static
     */
    private function _run(...$args): static
    {
        if ($this->modexWorkflowHasRun) {
            throw ModexRoutineException::routineAlreadyRun();
        }

        $workflow = null;
        if (\method_exists($this, 'workflow') && \is_callable([$this, 'workflow'])) {
            $workflow = [$this, 'workflow'];
        }

        if (!\is_callable($workflow)) {
            throw ModexWorkflowException::missingWorkflowMethod();
        }

        $definer = fn() => \call_user_func_array($workflow, $args);

        $this->modex = Modex::new()
            ->import($definer)
            ->run();

        $this->modexWorkflowHasRun = true;

        return $this;
    }



    /**
     * Get the result of the workflow.
     *
     * @return mixed
     */
    public function result()
    {
        // Note: the return type is not specified in PHP.
        // This is so the child class can override the return type to make it more specific

        return $this->modex->result();
    }
}
