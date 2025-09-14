<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Framework;

use DigitalAnomaly\AlteredLogic\Common\Enums\FrameworksEnum;
use DigitalAnomaly\AlteredLogic\Common\Enums\HttpClientEnum;
use Illuminate\Foundation\Application;

/**
 * Detect which framework is being used.
 */
final class CapabilityDetector
{
    /**
     * Check if Laravel is present.
     *
     * @return boolean
     */
    public static function laravelIsPresent(): bool
    {
        if (!\class_exists('Illuminate\Support\Facades\Facade')) {
            return false;
        }

        if (!\function_exists('app')) {
            return false;
        }

        // if (!\app() instanceof Application) {
        //     return false;
        // }

        return true;
    }

    // todo - add other frameworks



    /**
     * Check if cURL is present.
     *
     * @return boolean
     */
    public static function curlIsPresent(): bool
    {
        return \extension_loaded('curl');
    }



    /**
     * Pick the first available option, based on the order of preference.
     *
     * @param array<FrameworksEnum|HttpClientEnum> $options The options to pick from.
     * @return FrameworksEnum
     */
    public static function pickFunctionalityToUse(array $options): FrameworksEnum|HttpClientEnum|null
    {
        foreach ($options as $option) {

            // todo - add other frameworks

            $return = match ($option) {

                FrameworksEnum::Laravel => CapabilityDetector::laravelIsPresent() ? $option : null,
                // FrameworksEnum::Symfony => CapabilityDetector::symfonyIsPresent() ? $option : null,
                FrameworksEnum::NoFramework => $option,

                HttpClientEnum::Laravel => CapabilityDetector::laravelIsPresent() ? $option : null,
                // HttpClientEnum::Symfony => CapabilityDetector::symfonyIsPresent() ? $option : null,
                HttpClientEnum::Curl => CapabilityDetector::curlIsPresent() ? $option : null,

                default => null,
            };

            if ($return !== null) {
                return $return;
            }
        }

        return null;
    }

    // /**
    //  * Pick the available options from the given options (in order of preference).
    //  *
    //  * @param array<FrameworksEnum|HttpClientEnum> $options The options to pick from.
    //  * @return array<FrameworksEnum|HttpClientEnum>
    //  */
    // public static function pickAvailableFunctionality(array $options): array
    // {
    //     $availableOptions = [];
    //     foreach ($options as $option) {

    //         if (self::pickFunctionalityToUse([$option]) !== null) {
    //             $availableOptions[] = $option;
    //         }
    //     }

    //     return $availableOptions;
    // }
}
