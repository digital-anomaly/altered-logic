<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex;

/**
 * Stores instructions on how a Modex should proceed.
 */
final class ModexControl
{
    /** @var boolean Whether the Modex should return a value or not. */
    public private(set) bool $hasReturn = false;

    /** @var boolean Whether the Modex should return a value immediately or not. */
    public private(set) bool $returnNow = false;

    /** @var mixed The value to return. */
    public private(set) mixed $returnValue = null;



    /**
     * Private constructor.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {}



    /**
     * Instruct the Modex to return a value after the current step has completed
     * (i.e. the other function calls have been made).
     *
     * @param mixed $value The value to return.
     * @return self
     */
    public static function return(mixed $value = null): self
    {
        $control = new self();

        $control->hasReturn = true;
        $control->returnNow = false;
        $control->returnValue = $value;

        return $control;
    }

    /**
     * Instruct the Modex to return a value immediately.
     *
     * @param mixed $value The value to return.
     * @return self
     */
    public static function returnNow(mixed $value = null): self
    {
        $control = new self();

        $control->hasReturn = true;
        $control->returnNow = true;
        $control->returnValue = $value;

        return $control;
    }
}
