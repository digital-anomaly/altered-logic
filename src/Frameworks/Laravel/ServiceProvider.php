<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Frameworks\Laravel;

use DigitalAnomaly\AlteredLogic\Documents\DocConfig;
use DigitalAnomaly\AlteredLogic\Embed\EmbedConfig;
use DigitalAnomaly\AlteredLogic\Modex\ModexConfig;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Laravel Service Provider for the alteredLogic package.
 */
class ServiceProvider extends BaseServiceProvider
{
    /** @var string The path to the config file. */
    private const string CONFIG_PATH = __DIR__ . '/config.php';

    /** @var string The path to the config file. */
    public const string CONFIG_NAME = 'digital_anomaly.altered_logic';



    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes(
            [self::CONFIG_PATH => \config_path(self::CONFIG_NAME . '.php')],
            'config'
        );
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfig();

        $this->initialiseGlobalConfiguration();
    }



    /**
     * Merge the config from the config file.
     *
     * @return void
     */
    private function mergeConfig(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, self::CONFIG_NAME);
    }

    /**
     * Initialise the global configuration.
     *
     * @return void
     */
    private function initialiseGlobalConfiguration(): void
    {
        $prefix = self::CONFIG_NAME;

        EmbedConfig::debugLevel(Config::integer("{$prefix}.debug_levels.embeddings"));
        DocConfig::debugLevel(Config::integer("{$prefix}.debug_levels.documents"));
        ModexConfig::debugLevel(Config::integer("{$prefix}.debug_levels.modex"));

        EmbedConfig::deferBatchSize(Config::integer("{$prefix}.batch_sizes.embeddings"));
        DocConfig::deferBatchSize(Config::integer("{$prefix}.batch_sizes.documents"));
    }
}
