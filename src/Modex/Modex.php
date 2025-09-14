<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex;

use CodeDistortion\Backoff\Backoff;
use DigitalAnomaly\AlteredLogic\Adapters\Resolvers\HttpClientResolver;
use DigitalAnomaly\AlteredLogic\Exceptions\ModexException;
use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTxnInputDTO;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexCurrentState;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexDebug;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexSchemas;
use DigitalAnomaly\AlteredLogic\Modex\Internal\Thread;
// use DigitalAnomaly\AlteredLogic\Modex\Internal\Traits\HasBackoffTrait;
use DigitalAnomaly\AlteredLogic\Modex\Internal\Traits\HasLoopOrchestratorTrait;
use DigitalAnomaly\AlteredLogic\Modex\Internal\Traits\HasModexDialogueTrait;
use DigitalAnomaly\AlteredLogic\Modex\Internal\Traits\HasModexSchemasTrait;
use DigitalAnomaly\AlteredLogic\Modex\Internal\Traits\HasModexSettingsTrait;
use DigitalAnomaly\AlteredLogic\Modex\Internal\Traits\HasModexStateTrait;
use DigitalAnomaly\AlteredLogic\Modex\Internal\Traits\HasProfileConfigurationTrait;
use DigitalAnomaly\AlteredLogic\Registry\Registry;
use DigitalAnomaly\AlteredLogic\Support\Class\CallableInspector;
use DigitalAnomaly\AlteredLogic\Support\DebugLevelHelper;
use DigitalAnomaly\AlteredLogic\Support\Framework\DependencyInjection;

/**
 * Modex (Multimodal Exchange). A class for handling interactions with AI providers, enabling exchange of text, images
 * and other content types.
 */
final class Modex
{
    // use HasBackoffTrait;
    use HasModexDialogueTrait;
    use HasModexSchemasTrait;
    use HasModexSettingsTrait;
    use HasModexStateTrait;
    use HasLoopOrchestratorTrait;
    use HasProfileConfigurationTrait;



    /** @var boolean Whether the Modex is currently calling the prepareLoop() method or not. */
    private bool $isRunningLoopOrchestrator = false;

    /** @var boolean Whether the Modex is currently initialising or not (starts off as initialising). */
    private bool $isInitialising = false;

    /** @var array<string,boolean> The methods that have been called during initialisation. */
    private array $calledInitialisationMethods = [];

    /** @var boolean Whether the current request is executing the Modex process or not. */
    private bool $isCurrentlyExecuting = false;

    // /** @var boolean Whether the Modex was built from a callable definer or not. */
    // private bool $isInstanced = false;



    /** @var mixed The value returned by the callable or ModexRoutine's ->initialise() method. */
    private mixed $definerReturnValue = null;



    /** @var Thread|null The thread to use. */
    private ?Thread $thread = null;



    /** @var mixed The response from the AI provider. */
    private mixed $result = null;

    // todo - add other things to expose to the caller - like logs, token usage etc



    /** @var integer|null The debug level to use: 0 = off, 1 = basic, 2 = verbose, null = use the default. */
    private ?int $debugLevel = null { set(?int $value) => DebugLevelHelper::normaliseLevel($value); } // @phpcs:ignore





    /**
     * Set the debug level to use: 0 = off, 1 = basic, 2 = verbose, null = use the default.
     *
     * @param integer|null $level The debug level to use: 0 = off, 1 = basic, 2 = verbose, null = use the default.
     * @return self
     */
    public function debugLevel(?int $level = 1): self
    {
        $this->debugLevel = $level;

        return $this;
    }





    /**
     * Create a new, unconfigured Modex instance.
     *
     * When using a framework, its instantiated using the framework's dependency injection functionality.
     *
     * @return self
     */
    public static function new()
    {
        // Note: the return type is not specified in PHP.
        // This is so the framework can return a mock, intended to act like a Modex instance

        /** @var self $instance */
        $instance = DependencyInjection::instantiate(self::class);

        // check the backtrace to see if Modex->import() is being called
        foreach (\debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS) as $trace) {

            if (($trace['class'] ?? null) !== self::class) {
                continue;
            }
            if ($trace['function'] !== 'import') {
                continue;
            }

            $instance->isInitialising = true;
            break;
        }

        return $instance;
    }





    /**
     * Use a callable to configure the Modex, defined by the given callable, class, ModexRoutine or ModexWorkflow.
     *
     * The callable is called using dependency injection, and the following parameters are passed:
     * - Modex::class => the Modex instance
     * - 'modex' => the Modex instance
     *
     * @param callable|class-string|array{0:class-string,1:string}|self $definer The callable that defines the Modex.
     * @return self
     */
    public function import(callable|string|array|self $definer): self
    {
        // resolve the callable (that will define the Modex) from the given definer.
        $definer = $definer instanceof self
            ? $definer
            : CallableInspector::newDefiner($definer)->deriveCallable();

        // if it's already a Modex instance, copy the settings from it
        if ($definer instanceof self) {
            $this->copyModexSettings($definer);
            return $this;
        }



        // run the definer to initialise the Modex

        $this->isInitialising = true;
        $this->calledInitialisationMethods = [];

        try {

            // todo - run this in a try-catch block ?
            $definerReturnValue = DependencyInjection::call(
                $definer,
                [Modex::class => $this], // offer to pass $this as the Modex parameter
                ['modex' => $this],      // offer to pass $this as the parameter called $modex
            );

            // if the definer returned a Modex instance, copy the settings from it
            if ($definerReturnValue instanceof Modex) {

                // todo - check if the new modex had any calls to ->tool(), etc - if so, throw an exception
                $this->copyModexSettings($definerReturnValue);

            // otherwise, let the definer return a value to return to the caller
            } else {
                $this->definerReturnValue = $definerReturnValue;
            }

            // if there's a loop orchestrator, check to make sure the initialisation method
            // didn't set any data that gets reset before calling the loop orchestrator
            if ($this->hasLoopOrchestrator()) {
                $this->throwIfInitialisationMethodsWereCalled($this->calledInitialisationMethods);
            }

        } finally {
            $this->isInitialising = false;
        }

        return $this;
    }

    /**
     * Copy the settings from the given Modex instance into this instance.
     *
     * @param self $modex The Modex instance to copy the settings from.
     * @return void
     */
    private function copyModexSettings(self $modex): void
    {
        $properties = \get_object_vars($modex);
        foreach ($properties as $key => $value) {
            $this->$key = $value; // @phpstan-ignore-line
        }
    }







    /**
     * Run the Modex process.
     *
     * If a $definer is passed, it's imported first beforehand.
     *
     * @param callable|class-string|self|null $definer The callable that defines the Modex.
     * @param array<mixed>                    $args    The arguments to pass to the initialiser (if any).
     * @return self
     */
    public function run(callable|string|self|null $definer = null, array $args = []): self
    {
        if ($this->isInitialising) {
            throw new \Exception('->run() cannot be called while initialising'); // todo - add a custom exception
        }

        if ($this->isRunningLoopOrchestrator) {
            throw new \Exception('->run() cannot be called while preparing the loop'); // todo - add a custom exception
        }

        if ($definer !== null) {
            $this->import($definer);
        }

        $this->performRun($args);

        return $this;
    }

    /**
     * Actually run the Modex process.
     *
     * @param array<mixed> $args The arguments to pass to the initialiser (if any).
     * @return void
     */
    private function performRun(array $args = []): void
    {
        $this->isCurrentlyExecuting = true;
        $this->calledInitialisationMethods = []; // forget this list, it isn't needed anymore

        try {

            $this->result = null;

            // // if the definer returned a ModexControl and it wants to return a value, return that now
            // $control = $this->definerReturnValue;
            // if ($control instanceof ModexControl && $control->hasReturn) {
            //     $this->result = $control->returnValue;
            //     return;
            // }



            // let the loop orchestrator prepare the first step
            $control = $this->orchestrateNextStep(null);

            // if the loop orchestrator returned a ModexControl and it wants to return a value, return that now
            if ($control instanceof ModexControl && $control->hasReturn) {
                $this->result = $control->returnValue;
                return;
            }



            // if the definer or loop orchestrator didn't add any messages,
            // then the definer might have been designed to coordinate other
            // Modex calls instead of actually doing Modex interactions itself,
            // so return the value the definer returned
            if (!$this->getDialogue()->hasUnsentMessages()) {
                $this->result = $this->definerReturnValue;
                return;
            }



            do {

                // send the request to the AI provider
                $this->sendIndividualRequest();
                $response = $this->formatLlmResponse();

                // execute the function calls that the LLM requested, if any
                $control = $this->getThread()->executeNewFunctionCalls();

                // "archive" the messages, so they're not re-sent next time
                $this->resetDialogue(true);
                // add the response/s from function calls to the message list, so they are sent next time
                $this->addFunctionCallResultsToMessageList();

                // if the function calls returned a ModexControl and it wants to return a value, return that now
                if ($control instanceof ModexControl && $control->hasReturn) {
                    $this->result = $control->returnValue;
                    return;
                }



                // let the loop orchestrator prepare the next step
                $control = $this->orchestrateNextStep($response);

                // if the loop orchestrator returned a ModexControl and it wants to return a value, return that now
                if ($control instanceof ModexControl && $control->hasReturn) {
                    $this->result = $control->returnValue;
                    return;
                }



                // stop if the LLM didn't request any function calls (i.e. the LLM is done with this interaction)
                if (!$this->getThread()->lastResponseContainedFunctionCalls()) {
                    break;
                }

                // stop if the maximum number of steps has been reached
                if ($this->getThread()->hasExhaustedMaxSteps()) {
                    break;
                }



                // // todo - remove this?
                // // pass control to another Modex instance
                // if ($nextModex !== $this) {
                //     // todo - this is a "handoff" - update the code to use this name. e.g. $modex->handOff($nextModex);
                //     return $nextModex->performRun();
                // }

            } while (true);

        } finally {
            $this->isCurrentlyExecuting = false;
        }



        $this->result = $response;
    }



    /**
     * Get the result of the Modex process.
     *
     * @return mixed
     */
    public function result(): mixed
    {
        return $this->result;
    }



    /**
     * Get the thread to use.
     *
     * @return Thread
     */
    private function getThread(): Thread
    {
        return $this->thread ??= new Thread($this->getSettings()->maxSteps);
    }



    /**
     * Send a single request, and record the interaction.
     *
     * @return void
     */
    private function sendIndividualRequest(): void
    {
        $temp = $this->resolveModelProfile()->buildModexApiClient();
        ['apiClient' => $apiClient, 'modexModel' => $modexModel] = $temp;

        self::ensureHttpRequestsArentBlocked($modexModel->getModel());

        $settings = $this->getSettings();
        $schemas = $this->getModexSchemasReadyForSending($this->getState());
        $dialogue = $this->getDialogue();
        $modexInput = new ModexTxnInputDTO($settings, $schemas, $dialogue);

        $thread = $this->getThread();
        $requestNumber = $thread->nextStepNumber();



        if ($this->debugLevel !== null) {
            $debugLevel = $this->debugLevel;
        } elseif (Registry::modexConfig()->debugLevel !== null) {
            $debugLevel = Registry::modexConfig()->debugLevel;
        } else {
            $debugLevel = 0;
        }

        $debug = new ModexDebug($debugLevel);



        $requestBody = $apiClient->buildRequestBody($modexInput, $thread->prevResponseId());
        $debug->showRequestBodyDebug($requestNumber, $requestBody);
        $debug->showRequestSummaryDebug($requestNumber, $modexInput);



        $backoff = Backoff::exponentialMs(5, 2)->immediateFirstRetry()->maxAttempts(4);
        $httpClient = HttpClientResolver::buildHttpClient($backoff);
        $httpTxn = $apiClient->sendRequest($httpClient, $requestBody);
        $modexTxn = $apiClient->buildResponse($modexInput, $httpTxn);
        $thread->addTxn($httpTxn, $modexTxn);



        $responseBody = $httpTxn->response->body ?? '';
        $debug->showResponseBodyDebug($requestNumber, $httpTxn, $modexTxn, $responseBody);
        $debug->showResponseDebug($requestNumber, $httpTxn, $modexTxn);
    }

    /**
     * Throw an exception if HTTP requests are blocked.
     *
     * @param string $modelName The name of the model that was being used (for the exception message).
     * @return void
     * @throws ModexException If HTTP requests are blocked.
     */
    private static function ensureHttpRequestsArentBlocked(string $modelName): void
    {
        if (Registry::generalConfig()->blockRequests) {
            throw ModexException::httpRequestsAreBlocked($modelName);
        }

        if (Registry::modexConfig()->blockRequests) {
            throw ModexException::httpRequestsAreBlocked($modelName);
        }
    }



    /**
     * Add the function-call results to the message list, ready for the next request.
     *
     * @return void
     */
    private function addFunctionCallResultsToMessageList(): void
    {
        $functionCallInstances = $this->getThread()->getNewFunctionCallInstances();

        foreach ($functionCallInstances as $functionCallInstance) {
            $this->userMessage($functionCallInstance->buildUserMessage());
        }
    }







    /**
     * Process the response, and return the most relevant response (text, structured response etc).
     *
     * If the structured response is based on a callable, call the callable and return the result it gives.
     *
     * @return mixed
     */
    private function formatLlmResponse(): mixed
    {
        $modexTxn = $this->getThread()->getLatestModexTxn();
        if ($modexTxn === null) {
            return null;
        }

        return $modexTxn->getResponse();
    }







    /**
     * Call the loop orchestrator (if present) to configure the Modex for the next step.
     *
     * @param mixed $response The response from the most recent ModexTxn.
     * @return ModexControl|null
     */
    private function orchestrateNextStep(mixed $response = null): ?ModexControl
    {
        if (!$this->hasLoopOrchestrator()) {
            return null;
        }



        // remove tools and structured response, so ::prepareLoop() can add them again as desired
        $this->resetSchemas();

        // todo - run this in a try-catch block ?
        $this->isRunningLoopOrchestrator = true;
        $control = $this->callLoopOrchestrator($response);
        $this->isRunningLoopOrchestrator = false;

        return $control instanceof ModexControl
            ? $control
            : null;
    }







    /**
     * Specify that the response should be a boolean.
     *
     * @param string $description The description to use.
     * @return self
     */
    public function booleanResponse(string $description = ''): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getObjectToUpdateAddResponseTypeIn()->booleanResponse($description);

        return $this;
    }

    /**
     * Specify that the response should be an integer.
     *
     * @param string $description The description to use.
     * @return self
     */
    public function integerResponse(string $description = ''): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getObjectToUpdateAddResponseTypeIn()->integerResponse($description);

        return $this;
    }

    /**
     * Specify that the response should be a float.
     *
     * @param string $description The description to use.
     * @return self
     */
    public function floatResponse(string $description = ''): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getObjectToUpdateAddResponseTypeIn()->floatResponse($description);

        return $this;
    }

    /**
     * Specify that the response should be a string (free-text).
     *
     * @return self
     */
    public function stringResponse(): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getObjectToUpdateAddResponseTypeIn()->stringResponse();

        return $this;
    }

    /**
     * Specify that the response should be an enum.
     *
     * @param string[] $values      The allowed values.
     * @param string   $description The description to use.
     * @return self
     */
    public function enumResponse(array $values, string $description = ''): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getObjectToUpdateAddResponseTypeIn()->enumResponse($values, $description);

        return $this;
    }

    /**
     * Specify a structured response to be returned.
     *
     * @param callable|object|array{0:class-string|object,1:string}|class-string|string $type The structure to use.
     * @return self
     */
    public function structuredResponse(callable|object|array|string $type): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getObjectToUpdateAddResponseTypeIn()->structuredResponse($type);

        return $this;
    }





    /**
     * Specify that the response should be an array of booleans.
     *
     * @param string $description The description to use.
     * @return self
     */
    public function booleanArrayResponse(string $description = ''): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getObjectToUpdateAddResponseTypeIn()->booleanArrayResponse($description);

        return $this;
    }

    /**
     * Specify that the response should be an array of integers.
     *
     * @param string $description The description to use.
     * @return self
     */
    public function integerArrayResponse(string $description = ''): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getObjectToUpdateAddResponseTypeIn()->integerArrayResponse($description);

        return $this;
    }

    /**
     * Specify that the response should be an array of floats.
     *
     * @param string $description The description to use.
     * @return self
     */
    public function floatArrayResponse(string $description = ''): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getObjectToUpdateAddResponseTypeIn()->floatArrayResponse($description);

        return $this;
    }

    /**
     * Specify that the response should be an array of strings (free-text).
     *
     * @param string $description The description to use.
     * @return self
     */
    public function stringArrayResponse(string $description = ''): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getObjectToUpdateAddResponseTypeIn()->stringArrayResponse($description);

        return $this;
    }

    /**
     * Specify that the response should be an array of enums.
     *
     * @param string[] $values      The allowed values.
     * @param string   $description The description to use.
     * @return self
     */
    public function enumArrayResponse(array $values, string $description = ''): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getObjectToUpdateAddResponseTypeIn()->enumArrayResponse($values, $description);

        return $this;
    }

    /**
     * Specify that an array of a structured response should be returned.
     *
     * @param callable|object|array{0:class-string|object,1:string}|class-string|string $type The structure to use.
     * @return self
     */
    public function structuredResponseArrayOf(callable|object|array|string $type): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getObjectToUpdateAddResponseTypeIn()->structuredResponseArrayOf($type);

        return $this;
    }





    /**
     * Get the ModexState or ModexSchemas object to add a response type to.
     *
     * @return ModexCurrentState|ModexSchemas
     */
    private function getObjectToUpdateAddResponseTypeIn(): ModexCurrentState|ModexSchemas
    {
        if ($this->callerIsOverridingResponseType()) {

            $this->getState()->hasResponseTypeOverride = true;

            return $this->getState();
        }

        return $this->getSchemas();
    }

    /**
     * Check if the caller is overriding the response type or not.
     *
     * @return boolean
     */
    private function callerIsOverridingResponseType(): bool
    {
        return !$this->isInitialising && !$this->isRunningLoopOrchestrator;

        // return $this->isInstanced && !$this->currentlyRunningSend;
    }

    /**
     * Keep track of the methods called during initialisation. If it's determined after that a loop orchestrator has
     * been set, an exception will be thrown.
     *
     * @param string $method The method name that was called.
     * @return void
     */
    private function trackInitialisationMethodCall(string $method): void
    {
        if (!$this->isInitialising) {
            return;
        }

        $this->calledInitialisationMethods[$method] = true;
    }
}
