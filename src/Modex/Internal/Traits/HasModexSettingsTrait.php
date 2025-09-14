<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal\Traits;

use DigitalAnomaly\AlteredLogic\Modex\Internal\ModexSettings;

/**
 * Trait that provides methods to configure the settings to include in a Modex request.
 */
trait HasModexSettingsTrait
{
    /** @var ModexSettings The settings for the Modex request. */
    private ModexSettings $settings;



    /**
     * Get the ModexSettings instance.
     *
     * @return ModexSettings
     */
    protected function getSettings(): ModexSettings
    {
        return $this->settings ??= new ModexSettings();
    }







    /**
     * Specify the temperature to use in the request.
     *
     * @param float $temperature The temperature to use.
     * @return self
     */
    public function temperature(float $temperature): self
    {
        $this->getSettings()->temperature($temperature);

        return $this;
    }

    /**
     * Specify the topP to use in the request.
     *
     * @param float $topP The topP value to use.
     * @return self
     */
    public function topP(float $topP): self
    {
        $this->getSettings()->topP($topP);

        return $this;
    }



    /**
     * Specify the maximum output tokens for the request.
     *
     * @param integer $maxOutputTokens The maximum output tokens to use.
     * @param boolean $truncate        Whether to truncate output when exceeding max output tokens, or return an error.
     * @return self
     */
    public function maxOutputTokens(int $maxOutputTokens, bool $truncate = true): self
    {
        $this->getSettings()->maxOutputTokens($maxOutputTokens, $truncate);

        return $this;
    }



    /**
     * Specify an identifier for the current user to use in the request.
     *
     * Helps identify the user in the case of tracking abuse.
     *
     * @param string $userIdentifier The identifier to add.
     * @return self
     */
    public function userIdentifier(string $userIdentifier): self
    {
        $this->getSettings()->userIdentifier($userIdentifier);

        return $this;
    }



    /**
     * Specify the maximum number of steps to take when interacting with the AI provider.
     *
     * @param integer|null $maxSteps The maximum number of steps to use.
     * @return self
     */
    public function maxSteps(?int $maxSteps): self
    {
        $this->getSettings()->maxSteps($maxSteps);

        return $this;
    }

    /**
     * Specify that there is no step limit.
     *
     * @return self
     */
    public function noStepLimit(): self
    {
        $this->getSettings()->maxSteps(null);

        return $this;
    }
}
