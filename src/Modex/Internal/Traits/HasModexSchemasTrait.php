<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal\Traits;

use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Schemas\ToolInterface;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexCurrentState;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexSchemas;
use DigitalAnomaly\AlteredLogic\Modex\Modex;

/**
 * Trait that provides methods to configure the tools and structured response for a Modex request.
 *
 * @mixin Modex
 */
trait HasModexSchemasTrait
{
    /** @var ModexSchemas The ModexSchemas instance for the Modex request. */
    private ModexSchemas $schemas;



    /**
     * Get the ModexSchemas instance.
     *
     * @return ModexSchemas
     */
    protected function getSchemas(): ModexSchemas
    {
        return $this->schemas ??= new ModexSchemas();
    }

    /**
     * Reset the list of tools and structured response - for use when continuing the conversation, so it starts with
     * none defined.
     *
     * @return void
     */
    protected function resetSchemas(): void
    {
        $this->schemas = new ModexSchemas();
    }





    /**
     * Get the ModexSchemas instance with the overridden response type.
     *
     * @param ModexCurrentState $state The ModexState to get the override from (if available).
     * @return ModexSchemas
     */
    protected function getModexSchemasReadyForSending(ModexCurrentState $state): ModexSchemas
    {
        $schemas = clone $this->getSchemas();

        self::overrideSchemaResponseType($schemas, $state);

        return $schemas;
    }

    /**
     * Override the ModexSchemas responseType if desired.
     *
     * @param ModexSchemas      $schemas The ModexSchemas to override.
     * @param ModexCurrentState $state   The ModexCurrentState to get the override from (if available).
     * @return void
     */
    private static function overrideSchemaResponseType(ModexSchemas $schemas, ModexCurrentState $state): void
    {
        if ($state->hasResponseTypeOverride) {
            $schemas->setStructuredResponse($state->structuredResponse);
        }
    }







    /**
     * Add to the list of tools to use.
     *
     * @param callable|object|array{0:class-string|object,1:string}|class-string|string|ToolInterface $tool The tool to add.
     * @param string|null                                                                             $name The name of the tool.
     * @return self
     */
    public function tool(callable|object|array|string $tool, ?string $name = null): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getSchemas()->tool($tool, $name);

        return $this;
    }

    /**
     * Add to the list of tools to use.
     *
     * @param array<integer|string,callable|object|array{0:class-string|object,1:string}|class-string|string|ToolInterface> $tools The tools to add.
     * @return self
     */
    public function tools(array $tools): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getSchemas()->tools($tools);

        return $this;
    }

    /**
     * Add web-search to the list of tools.
     *
     * @return self
     */
    public function webSearchTool(): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getSchemas()->webSearchTool();

        return $this;
    }





    /**
     * Specify which tools that are allowed to be called (limit of 1 for OpenAI Responses API).
     *
     * @param array<ToolInterface|string> $tools The tools to allow.
     * @return self
     */
    public function onlyCallTools(array $tools): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getSchemas()->onlyCallTools($tools);

        return $this;
    }

    /**
     * Allow tools to be called.
     *
     * @return self
     */
    public function allowToolCalls(): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getSchemas()->allowToolCalls();

        return $this;
    }

    /**
     * Specify that no tools should be called.
     *
     * @return self
     */
    public function callNoTools(): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getSchemas()->callNoTools();

        return $this;
    }

    /**
     * Specify that at least one tool should be called.
     *
     * @return self
     */
    public function callAtLeastOneTool(): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getSchemas()->callAtLeastOneTool();

        return $this;
    }

    /**
     * Specify that at most one tool should be called.
     *
     * @return self
     */
    public function callAtMostOneTool(): self
    {
        $this->trackInitialisationMethodCall(__FUNCTION__);

        $this->getSchemas()->callAtMostOneTool();

        return $this;
    }
}
