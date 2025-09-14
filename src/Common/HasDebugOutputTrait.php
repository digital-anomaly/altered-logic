<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Common;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Trait for facilitating debug output.
 */
trait HasDebugOutputTrait
{
    /**
     * Constructor.
     *
     * @param integer $debugLevel The debug level: 0 = off, 1 = basic, 2 = verbose, null = use the default.
     */
    public function __construct(
        private int $debugLevel,
    ) {
    }



    /**
     * Check if the debug level is at least the given level.
     *
     * @param integer $level The debug level to check against.
     * @return boolean
     */
    private function debugLevelIsAtLeast(int $level): bool
    {
        return $this->debugLevel >= $level;
    }





    /**
     * Print a text message if debug mode is enabled.
     *
     * @param string      $message The message to print.
     * @param string|null $label   The label to print.
     * @return void
     */
    private function debugText(string $message, ?string $label = null): void
    {
        // black, red, green, yellow, blue, magenta, cyan, white, default, gray, bright-red,
        // bright-green, bright-yellow, bright-blue, bright-magenta, bright-cyan, bright-white
        $this->debugOutput('fg=default;bg=default', $message, $label);
    }

    /**
     * Print a success message if debug mode is enabled.
     *
     * @param string      $message The message to print.
     * @param string|null $label   The label to print.
     * @return void
     */
    private function debugSuccess(string $message, ?string $label = null): void
    {
        $this->debugOutput('fg=green', $message, $label);
    }

    /**
     * Print a warning message if debug mode is enabled.
     *
     * @param string      $message The message to print.
     * @param string|null $label   The label to print.
     * @return void
     */
    private function debugWarning(string $message, ?string $label = null): void
    {
        $this->debugOutput('fg=yellow', $message, $label);
    }

    /**
     * Print an error message if debug mode is enabled.
     *
     * @param string      $message The message to print.
     * @param string|null $label   The label to print.
     * @return void
     */
    private function debugError1a(string $message, ?string $label = null): void
    {
        $this->debugOutput('fg=bright-red', $message, $label);
    }

    /**
     * Print an error message if debug mode is enabled.
     *
     * @param string      $message The message to print.
     * @param string|null $label   The label to print.
     * @return void
     */
    private function debugError1b(string $message, ?string $label = null): void
    {
        $this->debugOutput('fg=red', $message, $label);
    }

    /**
     * Print a note message if debug mode is enabled.
     *
     * @param string      $message The message to print.
     * @param string|null $label   The label to print.
     * @return void
     */
    private function debugInfo1a(string $message, ?string $label = null): void
    {
        $this->debugOutput('fg=cyan', $message, $label);
    }

    /**
     * Print a note message if debug mode is enabled.
     *
     * @param string      $message The message to print.
     * @param string|null $label   The label to print.
     * @return void
     */
    private function debugInfo1b(string $message, ?string $label = null): void
    {
        // black, red, green, yellow, blue, magenta, cyan, white, default, gray, bright-red,
        // bright-green, bright-yellow, bright-blue, bright-magenta, bright-cyan, bright-white
        $this->debugOutput('fg=blue', $message, $label);
    }

    /**
     * Print a note message if debug mode is enabled.
     *
     * @param string      $message The message to print.
     * @param string|null $label   The label to print.
     * @return void
     */
    private function debugInfo2a(string $message, ?string $label = null): void
    {
        $this->debugOutput('fg=bright-yellow', $message, $label);
    }

    /**
     * Print a note message if debug mode is enabled.
     *
     * @param string      $message The message to print.
     * @param string|null $label   The label to print.
     * @return void
     */
    private function debugInfo2b(string $message, ?string $label = null): void
    {
        $this->debugOutput('fg=yellow', $message, $label);
    }



    /**
     * Print a message if debug mode is enabled.
     *
     * @param string $style   The style to use.
     * @param string $message The message to print.
     * @param string $label   The label to print.
     * @return void
     */
    private function debugOutput(?string $style, string $message, ?string $label = null): void
    {
        if (!$this->debugLevelIsAtLeast(1)) {
            return;
        }

        $io = new SymfonyStyle(new ArrayInput([]), new ConsoleOutput());
        $io->block($message, $label, $style, '', false, false);
    }
}
