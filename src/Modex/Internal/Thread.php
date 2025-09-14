<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Messages\MessageInterface;
use DigitalAnomaly\AlteredLogic\Modex\DTOs\ModexTxnDTO;
use DigitalAnomaly\AlteredLogic\Modex\Internal\FunctionCallInstance;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ToolTypes\FunctionTool;
use DigitalAnomaly\AlteredLogic\Modex\Messages\DTOs\FunctionCallDTO;
use DigitalAnomaly\AlteredLogic\Modex\Messages\Payloads\FunctionCallsMessagePayload;
use DigitalAnomaly\AlteredLogic\Modex\ModexControl;
use DigitalAnomaly\AlteredLogic\Support\Http\DTOs\HttpTxnDTO;

/**
 * Collects transmissions that belong to the same thread.
 *
 * Builds FunctionCallInstances and executes them.
 *
 * Collates the messages that have been sent and received.
 */
final class Thread
{
    /** @var HttpTxnDTO[] The HTTP transmissions. */
    private array $httpTxns = [];

    /** @var ModexTxnDTO[] The Modex transmissions. */
    private array $modexTxns = [];

    /** @var array<FunctionCallInstance[]> The function call instances. */
    private array $functionCallInstances = [];

    /** @var MessageInterface[] All of the messages that have been sent and received. */
    private array $allMessages = [];

    /** @var integer|null The maximum number of steps to take. */
    private ?int $maxSteps = null;



    /**
     * Constructor.
     *
     * @param integer|null $maxSteps The maximum number of steps to take.
     */
    public function __construct(?int $maxSteps)
    {
        $this->maxSteps = $maxSteps;
    }





    /**
     * Add a pair of HTTP transmission and multimodal transmission to the set.
     *
     * @param HttpTxnDTO  $httpTxn  The HTTP transmission to add.
     * @param ModexTxnDTO $modexTxn The multimodal transmission to add.
     * @return void
     */
    public function addTxn(HttpTxnDTO $httpTxn, ModexTxnDTO $modexTxn): void
    {
        $this->httpTxns[] = $httpTxn;
        $this->modexTxns[] = $modexTxn;
        $this->functionCallInstances[] = $this->buildFunctionCallInstances($modexTxn);

        // todo - remove $allMessages if it's unused
        foreach ($modexTxn->response->messages ?? [] as $message) {
            $this->allMessages[] = $message;
        }
    }

    /**
     * Builds function call instances from the latest transmission.
     *
     * @param ModexTxnDTO $modexTxn The multimodal transmission to get the function call instances from.
     * @return array<FunctionCallInstance>
     */
    private function buildFunctionCallInstances(ModexTxnDTO $modexTxn): array
    {
        $functionCallInstances = [];

        foreach ($modexTxn->response->messages ?? [] as $message) {
            foreach ($message->getPayloads() as $payload) {

                if (!$payload instanceof FunctionCallsMessagePayload) {
                    continue;
                }

                foreach ($payload->calls as $functionCall) {

                    $functionTool = $modexTxn->request->schemas->availableTools[$functionCall->name] ?? null;

                    if (!$functionTool instanceof FunctionTool) {
                        continue;
                    }

                    $functionCallInstances[] = $this->buildFunctionCallInstance(
                        $functionTool,
                        $functionCall,
                    );
                }
            }
        }

        return $functionCallInstances;
    }

    /**
     * Build a function call instance.
     *
     * @param FunctionTool|null $functionTool        The tool to use.
     * @param FunctionCallDTO   $payloadFunctionCall The function call to build.
     * @return FunctionCallInstance
     */
    private function buildFunctionCallInstance(
        ?FunctionTool $functionTool,
        FunctionCallDTO $payloadFunctionCall
    ): FunctionCallInstance {

        $functionName = $payloadFunctionCall->name;
        if ($functionTool === null) {
            throw new \Exception("Tool {$functionName} not found"); // todo - throw a custom exception
        }

        return new FunctionCallInstance(
            $functionTool,
            $payloadFunctionCall,
        );
    }





    /**
     * Resolve what the next step number will be.
     *
     * @return integer
     */
    public function nextStepNumber(): int
    {
        return \count($this->httpTxns) + 1;
    }

    /**
     * Resolve what the previous response id was.
     *
     * @return string|integer|null
     */
    public function prevResponseId(): string|int|null
    {
        if (\count($this->modexTxns) === 0) {
            return null;
        }

        $lastModexTxn = \end($this->modexTxns);

        return $lastModexTxn->response->providerId ?? null;
    }

    /**
     * Check if the modex has exhausted the max steps.
     *
     * @return boolean
     */
    public function hasExhaustedMaxSteps(): bool
    {
        if (($this->maxSteps !== null) && (\count($this->httpTxns) >= $this->maxSteps)) {
            return true;
        }

        return false;
    }





    /**
     * Get the latest Modex transmission.
     *
     * @return ModexTxnDTO|null
     */
    public function getLatestModexTxn(): ?ModexTxnDTO
    {
        if (\count($this->modexTxns) === 0) {
            return null;
        }

        return \end($this->modexTxns);
    }





    /**
     * Execute the new function calls.
     *
     * @return ModexControl|null
     */
    public function executeNewFunctionCalls(): ?ModexControl
    {
        $control = null;
        foreach ($this->getNewFunctionCallInstances() as $functionCallInstance) {

            $nextControl = $functionCallInstance->call();

            if ($nextControl instanceof ModexControl) {

                $control = $nextControl;

                // stop calling the function calls when the function call returned
                // a ModexControl and it wants to return a value right now
                if ($nextControl->hasReturn && $nextControl->returnNow) {
                    break;
                }
            }
        }

        return $control;
    }

    /**
     * Check if the latest response contained function calls.
     *
     * @return boolean
     */
    public function lastResponseContainedFunctionCalls(): bool
    {
        return \count($this->getNewFunctionCallInstances()) > 0;
    }

    /**
     * Get the new function call instances.
     *
     * @return FunctionCallInstance[]
     */
    public function getNewFunctionCallInstances(): array
    {
        $index = \count($this->functionCallInstances) - 1;

        return $this->functionCallInstances[$index] ?? [];
    }
}
