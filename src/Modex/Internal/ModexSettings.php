<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal;

/**
 * Container for settings to include in a Modex request.
 *
 * @todo - update properties to use PHP 8.4's get / set
 */
final class ModexSettings
{
    /** @var float|null The temperature to use. */
    public private(set) ?float $temperature = null;

    /** @var float|null The topP to use. */
    public private(set) ?float $topP = null;

    /** @var integer|null The maximum output tokens to use.*/
    public private(set) ?int $maxOutputTokens = null;

    /** @var boolean|null Truncate the response when it exceeds the max output tokens. */
    public private(set) ?bool $truncateResponse = null;

    /** @var string|null Identifier for the current user. Helps when tracking abuse. */
    public private(set) ?string $userIdentifier = null;

    /** @var integer|null The maximum number of steps to take when interacting with the AI provider. */
    public private(set) ?int $maxSteps = 10;



    /**
     * Specify the temperature to use in the request.
     *
     * @param float $temperature The temperature to use.
     * @return void
     */
    public function temperature(float $temperature): void
    {
        $this->temperature = $temperature;
    }

    /**
     * Specify the topP to use in the request.
     *
     * @param float $topP The topP value to use.
     * @return void
     */
    public function topP(float $topP): void
    {
        $this->topP = $topP;
    }



    /**
     * Specify the maximum output tokens for the request.
     *
     * @param integer $maxOutputTokens The maximum output tokens to use.
     * @param boolean $truncate        Whether to truncate output when exceeding max output tokens, or return an error.
     * @return void
     */
    public function maxOutputTokens(int $maxOutputTokens, bool $truncate = true): void
    {
        $this->maxOutputTokens = $maxOutputTokens;
        $this->truncateResponse = $truncate;
    }



    /**
     * Specify an identifier for the current user to use in the request.
     *
     * Helps identify the user in the case of tracking abuse.
     *
     * @param string $userIdentifier The identifier to add.
     * @return void
     */
    public function userIdentifier(string $userIdentifier): void
    {
        $this->userIdentifier = $userIdentifier;
    }



    /**
     * Specify the maximum number of steps to take when interacting with the AI provider.
     *
     * @param integer|null $maxSteps The maximum number of steps to use.
     * @return void
     */
    public function maxSteps(?int $maxSteps): void
    {
        $this->maxSteps = $maxSteps;
    }

    /**
     * Specify that there is no step limit.
     *
     * @return void
     */
    public function noStepLimit(): void
    {
        $this->maxSteps = null;
    }
}
