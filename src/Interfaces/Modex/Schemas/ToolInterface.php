<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Interfaces\Modex\Schemas;

/**
 * Interface for Tools.
 */
interface ToolInterface
{
    /**
     * Get the name of the tool, this identifies it to the AI provider.
     *
     * @return string The name of the tool type.
     */
    public function getName(): string;
}
