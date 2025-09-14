<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex;

use DigitalAnomaly\AlteredLogic\Exceptions\ModexRoutineException;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\ModexStateInterface;
use DigitalAnomaly\AlteredLogic\Modex\ModexState;
use DigitalAnomaly\AlteredLogic\Support\Framework\DependencyInjection;
use TypeError;

/**
 * A structured Modex routine.
 *
 * @property ModexStateInterface $state The routine's storage location for state.
 * @method static run(mixed ...$args)
 */
abstract class ModexRoutine
{
    /** @var Modex The modex that this routine configures. */
    private Modex $modex;

    /** @var ModexState The routine's storage location for state. */
    private ModexState $_state {
        get => $this->_state ??= new ModexState();
    }

    /** @var callable[] Callbacks that configure the modex. */
    private array $modexConfigurationCallbacks = [];

    /** @var boolean Whether the routine has been run yet or not. */
    private bool $modexRoutineHasRun = false;





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
     * Create a new routine instance.
     *
     * When using a framework, its instantiated using the framework's dependency injection functionality.
     *
     * @return static
     */
    public static function new()
    {
        // Note: the return type is not specified in PHP.
        // This is so the framework can return a mock, intended to act like a ModexRoutine instance

        /** @var static $instance */
        $instance = DependencyInjection::instantiate(static::class);

        return $instance;
    }



    /**
     * Initialise the Modex - initial messages and configuration can be set here.
     *
     * This is called once before starting the Modex run.
     *
     * @return Modex
     */
    // public function initialise(): Modex
    // {
    //     // … to be overridden by child classes
    // }

    /**
     * Initialize the Modex - initial messages and configuration can be set here.
     *
     * This is called once before starting the Modex run.
     *
     * @return Modex
     */
    // public function initialize(): Modex
    // {
    //     // … to be overridden by child classes
    // }



    /**
     * Prepare the Modex, ready for the next call in the loop.
     *
     * This is called before each interaction with the AI provider and gives you an opportunity to make decisions and
     * alter the conversation.
     *
     * Note: The Modex's tools and response type are RESET before each call to ->prepareLoop().
     *
     * Provided there are new messages (including results from function calls), the Modex will continue to run.
     *
     * @param Modex $modex The current Modex.
     * @return ModexControl|null|void
     */
    // public function prepareLoop(Modex $modex): ?ModexControl
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
     * Add some custom configuration to the Modex instance at call-time.
     *
     * @param callable $callback The callback to configure the Modex instance.
     * @return static
     */
    final public function configureModex(callable $callback): static
    {
        $this->modexConfigurationCallbacks[] = $callback;

        return $this;
    }



    /**
     * Run the routine.
     *
     * The result can be collected afterwards using ->result.
     *
     * @param mixed ...$args Arguments to pass to the routine (if any).
     * @return static
     */
    private function _run(...$args): static
    {
        if ($this->modexRoutineHasRun) {
            throw ModexRoutineException::routineAlreadyRun();
        }



        // resolve the prepare and loop methods
        $initialise = match (true) {
            \method_exists($this, 'initialise') && \is_callable([$this, 'initialise']) => [$this, 'initialise'],
            \method_exists($this, 'initialize') && \is_callable([$this, 'initialize']) => [$this, 'initialize'],
            default => null,
        };

        $prepareLoop = null;
        if (\method_exists($this, 'prepareLoop') && \is_callable([$this, 'prepareLoop'])) {
            $prepareLoop = [$this, 'prepareLoop'];
        }

        if ($initialise === null && $prepareLoop === null) {
            throw ModexRoutineException::missingInitialiseAndPrepareLoopMethods();
        }



        $this->modex = Modex::new();

        // call the initialise method
        if (\is_callable($initialise)) {
            $definer = fn() => \call_user_func_array($initialise, $args);
            $this->modex->import($definer);
        }

        // set the prepare-loop method
        if (\is_callable($prepareLoop)) {
            $this->modex->prepareloop($prepareLoop);
        }

        // let the caller override any modex settings
        foreach ($this->modexConfigurationCallbacks as $configurationCallback) {
            DependencyInjection::call(
                $configurationCallback,
                [Modex::class => $this->modex],
                ['modex' => $this->modex],
            );
        }



        // run the modex
        $this->modex->run();

        $this->modexRoutineHasRun = true;

        return $this;
    }



    /**
     * Get the result of the routine.
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
