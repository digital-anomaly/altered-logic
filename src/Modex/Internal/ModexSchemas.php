<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal;

use DigitalAnomaly\AlteredLogic\Exceptions\ModexException;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Schemas\ToolInterface;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ToolTypes\FunctionTool;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ToolTypes\WebSearchTool;
use DigitalAnomaly\AlteredLogic\Modex\Internal\Traits\HasModexStructuredResponseTrait;
use DigitalAnomaly\AlteredLogic\Support\Class\CallableInspector;
use DigitalAnomaly\Schema\Types\CallableType;

/**
 * Container for tools and structured response, to include in a Modex request.
 */
final class ModexSchemas
{
    use HasModexStructuredResponseTrait;



    /** @var array<string,ToolInterface> The tools to make available. */
    public private(set) array $availableTools = [];

    /** @var array<string> Only allow these tools to be called (if specified).*/
    public private(set) array $onlyCallTools = [];

    /** @var boolean Whether to call no tools. */
    public private(set) bool $callNoTools = false;

    /** @var boolean Whether to call at least one tool. */
    public private(set) bool $callAtLeastOneTool = false;

    /** @var boolean Whether to call one tool at most. */
    public private(set) bool $callOneToolAtMost = false;







    /**
     * Add to the list of tools to use.
     *
     * @param callable|object|array{0:class-string|object,1:string}|class-string|string|ToolInterface $tool The tool to add.
     * @param string|null                                                                             $name The name of the tool.
     * @return void
     */
    public function tool(callable|object|array|string $tool, ?string $name = null): void
    {
        $tool = $this->resolveTool($tool, $name, true);

        $this->availableTools[$tool->getName()] = $tool;
    }

    /**
     * Add to the list of tools to use.
     *
     * @param array<integer|string,callable|object|array{0:class-string|object,1:string}|class-string|string|ToolInterface> $tools The tools to add.
     * @return void
     */
    public function tools(array $tools): void
    {
        foreach ($tools as $name => $tool) {

            if (\is_int($name)) {
                $name = null;
            }

            $tool = $this->resolveTool($tool, $name, false);

            $this->availableTools[$tool->getName()] = $tool;
        }
    }

    /**
     * Resolve a tool to a ToolInterface instance, build it if it isn't one.
     *
     * @param callable|object|array{0:class-string|object,1:string}|class-string|string|ToolInterface $tool       The tool to resolve.
     * @param string|null                                                                             $name       The name of the tool.
     * @param boolean                                                                                 $singleTool Whether the tool was defined by tool() or tools()
     * @return ToolInterface
     */
    private function resolveTool(object|array|string $tool, ?string $name, bool $singleTool = false): ToolInterface
    {
        // already a tool
        if ($tool instanceof ToolInterface) {
            return $tool;
        }

        $resolvedType = self::buildCallableType($tool, $name);

        // make sure the tool has a name
        if ($resolvedType->name === '') {
            $singleTool
                ? throw ModexException::toolNameNotSpecified()
                : throw ModexException::toolNameMustBeKey();
        }

        return new FunctionTool($resolvedType);
    }



    /**
     * Build a CallableType instance for a regular callable.
     *
     * @param callable|object|array{0:class-string|object,1:string}|class-string|string|ToolInterface $tool The tool to
     *                                                                                                      resolve.
     * @param string|null                                                                             $name The name of
     *                                                                                                      the tool.
     * @return CallableType
     * @throws ModexException When the tool is a class constructor.
     */
    private static function buildCallableType(object|array|string $tool, string|null $name): CallableType
    {
        // // special case - check to see if the $tool is a string
        // // which refers to a method from the calling object
        // if (\is_string($tool) && \mb_strpos($tool, '::') === false && !\class_exists($tool)) {

        //     $framesBack = 4;
        //     $backtrace = \debug_backtrace(
        //         \DEBUG_BACKTRACE_PROVIDE_OBJECT | \DEBUG_BACKTRACE_IGNORE_ARGS,
        //         $framesBack + 1,
        //     );
        //     $callingObject = $backtrace[$framesBack]['object'] ?? null;

        //     if (($callingObject !== null) && (\method_exists($callingObject, $tool))) {
        //         $name = $tool;
        //         $tool = [$callingObject, $tool];
        //     }
        // }



        return CallableInspector::newTool($tool)->buildCallableType($name);
    }





    /**
     * Add web-search to the list of tools.
     *
     * @return void
     */
    public function webSearchTool(): void
    {
        // todo - this tool's name is "web_search_preview", which is a part of OpenAI's Responses API - make this general somehow
        $this->tools([new WebSearchTool()]);
    }





    /**
     * Specify which tools that are allowed to be called (limit of 1 for OpenAI Responses API).
     *
     * @param array<ToolInterface|string> $tools The tools to allow.
     * @return void
     */
    public function onlyCallTools(array $tools): void
    {
        foreach ($tools as $tool) {

            if ($tool instanceof ToolInterface) {
                $tool = $tool->getName();
            }

            if (!\is_string($tool)) {
                throw new \Exception('Invalid tool type: ' . \gettype($tool)); // todo - add a custom exception.
            }

            $this->onlyCallTools[] = $tool;
        }

        $this->onlyCallTools = \array_unique($this->onlyCallTools);
    }

    /**
     * Allow tools to be called.
     *
     * @return void
     */
    public function allowToolCalls(): void
    {
        $this->callNoTools = false;
    }

    /**
     * Specify that no tools should be called.
     *
     * @return void
     */
    public function callNoTools(): void
    {
        $this->callNoTools = true;
        $this->callAtLeastOneTool = false;
        $this->callOneToolAtMost = false;
    }

    /**
     * Specify that at least one tool should be called.
     *
     * @return void
     */
    public function callAtLeastOneTool(): void
    {
        $this->callNoTools = false;
        $this->callAtLeastOneTool = true;
    }

    /**
     * Specify that at most one tool should be called.
     *
     * @return void
     */
    public function callAtMostOneTool(): void
    {
        $this->callNoTools = false;
        $this->callOneToolAtMost = true;
    }
}
