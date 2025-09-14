<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Frameworks\Laravel;

use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocSearcherInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocStoreInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedCacheInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedModelInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\ModexModelInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Providers\CredentialsInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Registry\FrameworkRegistryBuilderInterface;
use DigitalAnomaly\AlteredLogic\Profiles\DocumentProfile;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedCacheProfile;
use DigitalAnomaly\AlteredLogic\Profiles\EmbedModelProfile;
use DigitalAnomaly\AlteredLogic\Profiles\ModexModelProfile;
use DigitalAnomaly\AlteredLogic\Support\StringHelper;
use Exception;
use Illuminate\Config\Repository;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionParameter;

/**
 * Set up AlteredLogic credentials and profiles based on Laravel configuration.
 */
final class LaravelFrameworkRegistryBuilder implements FrameworkRegistryBuilderInterface
{
    private const string CONFIG_PREFIX__CREDENTIALS = 'credentials';

    private const string CONFIG_PREFIX__DEFAULT_PROFILES = 'default_profiles';

    private const string CONFIG_PREFIX__EMBED_MODEL_PROFILES = 'embed_model_profiles';
    private const string CONFIG_PREFIX__EMBED_MODELS = 'embed_models';

    private const string CONFIG_PREFIX__EMBED_CACHE_PROFILES = 'embed_cache_profiles';
    private const string CONFIG_PREFIX__EMBED_CACHES = 'embed_caches';

    private const string CONFIG_PREFIX__DOC_PROFILES = 'doc_profiles';
    private const string CONFIG_PREFIX__DOC_STORES = 'doc_stores';
    private const string CONFIG_PREFIX__DOC_SEARCHERS = 'doc_searchers';

    private const string CONFIG_PREFIX__MODEX_PROFILES = 'modex_profiles';
    private const string CONFIG_PREFIX__MODEX_MODELS = 'modex_models';





    /**
     * Build specific provider credentials from configuration.
     *
     * @param string $name The provider name.
     * @return CredentialsInterface|null
     * @throws Exception If credentials could not be built.
     */
    public static function buildCredentials(string $name): ?CredentialsInterface
    {
        $credentialsPrefix = self::CONFIG_PREFIX__CREDENTIALS;

        if (!self::configHas("{$credentialsPrefix}.{$name}")) {
            return null;
        }

        /** @var CredentialsInterface|null $credentials */
        $credentials = self::buildInstance(
            $name,
            "{$credentialsPrefix}.{$name}",
            CredentialsInterface::class,
        );

        // invalid credentials
        if ($credentials === null) {
            throw new Exception("Provider credentials '$name' could not be built"); // todo - add custom exception
        }

        return $credentials;
    }





    /**
     * Get the default embed model profile name from configuration.
     *
     * @return string
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultEmbedModelProfileName(): string
    {
        $defaultProfilesPrefix = self::CONFIG_PREFIX__DEFAULT_PROFILES;

        return self::configNonEmptyString("{$defaultProfilesPrefix}.embed_model_profile");
    }

    /**
     * Import a specific embed model profile from configuration.
     *
     * @param string $name Profile name.
     * @return EmbedModelProfile|null
     * @throws Exception If profile could not be built.
     */
    public static function buildEmbedModelProfile(string $name): ?EmbedModelProfile
    {
        $profilePrefix = self::CONFIG_PREFIX__EMBED_MODEL_PROFILES;
        $embedModelsPrefix = self::CONFIG_PREFIX__EMBED_MODELS;

        // only proceed if the user has defined configuration for this
        if (!self::configHas("{$profilePrefix}.{$name}.models")) {
            return null;
        }



        $profile = new EmbedModelProfile();

        // add the embed models

        $models = self::configNonEmptyArray("{$profilePrefix}.{$name}.models"); // needs to have at least one model
        foreach ($models as $modelName) {

            // can't resolve the model name
            if (!\is_string($modelName)) {
                $modelame = (string) $modelName;
                throw new Exception("Invalid embed model name: $modelame"); // todo - add custom exception
            }

            if (!self::configHas("{$embedModelsPrefix}.{$modelName}")) {
                throw new Exception("Embed model '$modelName' has not been defined"); // todo - add custom exception
            }

            /** @var EmbedModelInterface|null $model */
            $model = self::buildInstance(
                $modelName,
                "{$embedModelsPrefix}.{$modelName}",
                EmbedModelInterface::class,
            );

            // invalid model
            if ($model === null) {
                throw new Exception("Embed model '$modelName' could not be built"); // todo - add custom exception
            }

            $profile->addModel($model);
        }

        return $profile;
    }





    /**
     * Get the default embed cache profile name from configuration.
     *
     * @return string
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultEmbedCacheProfileName(): string
    {
        $defaultProfilesPrefix = self::CONFIG_PREFIX__DEFAULT_PROFILES;

        return self::configNonEmptyString("{$defaultProfilesPrefix}.embed_cache_profile");
    }

    /**
     * Import a specific embed cache profile from configuration.
     *
     * @param string $name Profile name.
     * @return EmbedCacheProfile|null
     * @throws Exception If profile could not be built.
     */
    public static function buildEmbedCacheProfile(string $name): ?EmbedCacheProfile
    {
        $profilePrefix = self::CONFIG_PREFIX__EMBED_CACHE_PROFILES;
        $embedCachesPrefix = self::CONFIG_PREFIX__EMBED_CACHES;

        // only proceed if the user has defined configuration for this
        if (!self::configHas("{$profilePrefix}.{$name}.caches")) {
            return null;
        }



        $profile = new EmbedCacheProfile();

        // add the embed caches

        $caches = self::configNonEmptyArray("{$profilePrefix}.{$name}.caches"); // needs to have at least one model
        foreach ($caches as $cacheName) {

            // can't resolve the cache name
            if (!\is_string($cacheName)) {
                $cacheName = (string) $cacheName;
                throw new Exception("Invalid embed cache name: $cacheName"); // todo - add custom exception
            }

            if (!self::configHas("{$embedCachesPrefix}.{$cacheName}")) {
                throw new Exception("Embed cache '$cacheName' has not been defined"); // todo - add custom exception
            }

            /** @var EmbedCacheInterface|null $cache */
            $cache = self::buildInstance(
                $cacheName,
                "{$embedCachesPrefix}.{$cacheName}",
                EmbedCacheInterface::class,
            );

            // invalid cache
            if ($cache === null) {
                throw new Exception("Embed cache '$cacheName' could not be built"); // todo - add custom exception
            }

            $profile->addCache($cache);
        }

        return $profile;
    }





    /**
     * Get the default document profile name from configuration.
     *
     * @return string
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultDocumentProfileName(): string
    {
        $defaultProfilesPrefix = self::CONFIG_PREFIX__DEFAULT_PROFILES;

        return self::configNonEmptyString("{$defaultProfilesPrefix}.doc_profile");
    }

    /**
     * Import a specific document profile from configuration.
     *
     * @param string $name Profile name.
     * @return DocumentProfile|null
     * @throws Exception If profile could not be built.
     */
    public static function buildDocumentProfile(string $name): ?DocumentProfile
    {
        $profilePrefix = self::CONFIG_PREFIX__DOC_PROFILES;
        $docStoresPrefix = self::CONFIG_PREFIX__DOC_STORES;
        $docSearchersPrefix = self::CONFIG_PREFIX__DOC_SEARCHERS;

        // only proceed if the user has defined configuration for this
        if (!self::configHas("{$profilePrefix}.{$name}.store")) {
            return null;
        }
        if (!self::configHas("{$profilePrefix}.{$name}.searchers")) {
            return null;
        }



        $docStoreName = self::configNonEmptyString("{$profilePrefix}.{$name}.store");

        // build the doc store

        if (!self::configHas("{$docStoresPrefix}.{$docStoreName}")) {
            throw new Exception("Doc store '$docStoreName' has not been defined"); // todo - add custom exception
        }

        /** @var DocStoreInterface|null $docStore */
        $docStore = self::buildInstance(
            $docStoreName,
            "{$docStoresPrefix}.{$docStoreName}",
            DocStoreInterface::class,
        );

        // invalid doc store
        if ($docStore === null) {
            throw new Exception("Doc store '$docStoreName' could not be built"); // todo - add custom exception
        }

        $profile = new DocumentProfile($docStore);

        // add the doc-searchers

        $searchers = self::configNonEmptyArray("{$profilePrefix}.{$name}.searchers"); // needs to have at least one model
        foreach ($searchers as $searcherName) {

            // can't resolve the searcher name
            if (!\is_string($searcherName)) {
                $searcherName = (string) $searcherName;
                throw new Exception("Invalid doc searcher name: $searcherName"); // todo - add custom exception
            }

            if (!self::configHas("{$docSearchersPrefix}.{$searcherName}")) {
                throw new Exception("Doc searcher '$searcherName' has not been defined"); // todo - add custom exception
            }

            /** @var DocSearcherInterface|null $searcher */
            $searcher = self::buildInstance(
                $searcherName,
                "{$docSearchersPrefix}.{$searcherName}",
                DocSearcherInterface::class,
            );

            // invalid searcher
            if ($searcher === null) {
                throw new Exception("Doc searcher '$searcherName' could not be built"); // todo - add custom exception
            }

            $profile->attachSearcher($searcher, $searcherName);
        }

        return $profile;
    }





    /**
     * Get the default modex model profile name from configuration.
     *
     * @return string
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultModexModelProfileName(): string
    {
        $defaultProfilesPrefix = self::CONFIG_PREFIX__DEFAULT_PROFILES;

        return self::configNonEmptyString("{$defaultProfilesPrefix}.modex_model_profile");
    }

    /**
     * Import a specific modex model profile from configuration.
     *
     * @param string $name Profile name.
     * @return ModexModelProfile|null
     * @throws Exception If profile could not be built.
     */
    public static function buildModexModelProfile(string $name): ?ModexModelProfile
    {
        $profilePrefix = self::CONFIG_PREFIX__MODEX_PROFILES;
        $modexModelsPrefix = self::CONFIG_PREFIX__MODEX_MODELS;

        // only proceed if the user has defined configuration for this
        if (!self::configHas("{$profilePrefix}.{$name}.models")) {
            return null;
        }



        $profile = new ModexModelProfile();

        // add the modex models

        $models = self::configNonEmptyArray("{$profilePrefix}.{$name}.models"); // needs to have at least one model
        foreach ($models as $modelName) {

            // can't resolve the model name
            if (!\is_string($modelName)) {
                $modelName = (string) $modelName;
                throw new Exception("Invalid modex model name: $modelName"); // todo - add custom exception
            }

            if (!self::configHas("{$modexModelsPrefix}.{$modelName}")) {
                throw new Exception("Modex model '$modelName' has not been defined"); // todo - add custom exception
            }

            /** @var ModexModelInterface|null $model */
            $model = self::buildInstance(
                $modelName,
                "{$modexModelsPrefix}.{$modelName}",
                ModexModelInterface::class,
            );

            // invalid model
            if ($model === null) {
                throw new Exception("Modex model '$modelName' could not be built"); // todo - add custom exception
            }

            $profile->addModel($model);
        }

        return $profile;
    }





    /**
     * Build an instance of a class based on Laravel config values.
     *
     * @param string $configName The name of the object in the config.
     * @param string $key        The Laravel config key to pick from.
     * @param string $interface  The interface the class should implement.
     * @return object|null
     * @throws Exception If the class cannot be built, or the class does not implement the interface.
     */
    private static function buildInstance(string $configName, string $key, string $interface): ?object
    {
        $class = self::configNonEmptyString("$key.type");
        if ($class === '') {
            return null;
        }

        $config = self::configArray($key);
        unset($config['type']);

        $instance = self::instantiateUsingReflection($class, $config);
        if (!$instance instanceof $interface) {

            $temp = \explode('\\', $interface);
            $interfaceName = $temp[\count($temp) - 1];

            throw new Exception("'$configName' is not a $interfaceName"); // todo - add custom exception
        }

        return $instance;
    }

    /**
     * Instantiate a class using reflection and config mapping.
     *
     * @param string  $class  Class to instantiate.
     * @param mixed[] $config Configuration array.
     * @return object
     * @throws Exception If the class cannot be built.
     */
    private static function instantiateUsingReflection(string $class, array $config): object
    {
        if (!\class_exists($class)) {
            throw new Exception("Class '$class' does not exist"); // todo - add custom exception
        }

        $reflection = new ReflectionClass($class);

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        // resolve the constructor parameters
        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $args[] = self::resolveParameterValue($class, $param, $config);
        }

        return new $class(...$args);
    }

    /**
     * Resolve a parameter value.
     *
     * @param string              $class  Class being instantiated.
     * @param ReflectionParameter $param  Parameter to resolve.
     * @param mixed[]             $config Configuration array.
     * @return mixed
     * @throws Exception If parameter value cannot be resolved.
     */
    private static function resolveParameterValue(string $class, ReflectionParameter $param, array $config): mixed
    {
        $paramName = $param->getName();
        $configKey = StringHelper::toSnakeCase($paramName);
        $value = null;

        if (\array_key_exists($configKey, $config)) {

            // use the value found in the config
            $value = $config[$configKey];

        } elseif ($param->isDefaultValueAvailable()) {

            // use the constructor parameter's default value
            $value = $param->getDefaultValue();
        }

        // determine an appropriate value when it was resolved to null
        if (($value === null) && (!$param->allowsNull())) {

            $type = $param->getType();
            if ($type !== null) {

                if ($type instanceof \ReflectionNamedType) {

                    $typeName = $type->getName();
                    $value = match ($typeName) {
                        'string' => '',
                        'array' => [],
                        'int' => 0,
                        'float' => 0.0,
                        'bool' => false,
                        default => throw new Exception("Cannot determine default value for required parameter '$paramName' of type '$typeName' in $class") // todo - add custom exception
                    };
                } else {
                    throw new Exception("Cannot determine default value for required parameter '$paramName' with complex type in $class"); // todo - add custom exception
                }
            }
        }

        return $value;
    }





    /**
     * Get a configuration array value.
     *
     * @param string  $name    Config key name.
     * @param mixed[] $default Default value to use.
     * @return mixed[]
     * @throws Exception If configuration value is invalid or doesn't exist.
     */
    private static function configArray(string $name, array $default = []): array
    {
        /** @var mixed[] $return */
        $return = self::configValue('array', $name, $default, \func_num_args() > 1);

        return $return;
    }

    /**
     * Get a configuration non-empty array value.
     *
     * @param string  $name    Config key name.
     * @param mixed[] $default Default value to use.
     * @return mixed[]
     * @throws Exception If configuration value is invalid or doesn't exist.
     */
    private static function configNonEmptyArray(string $name, array $default = []): array
    {
        /** @var mixed[] $return */
        $return = self::configValue('array', $name, $default, \func_num_args() > 1);

        if ($return === []) {
            throw new Exception("Laravel config key '$name' is an empty array"); // todo - add custom exception
        }

        return $return;
    }



    // /**
    //  * Get a configuration string value.
    //  *
    //  * @param string $name    Config key name.
    //  * @param string $default Default value to use.
    //  * @return string
    //  * @throws Exception If configuration value is invalid or doesn't exist.
    //  */
    // private static function configString(string $name, string $default = ''): string
    // {
    //     /** @var string $return */
    //     $return = self::configValue('string', $name, $default, \func_num_args() > 1);

    //     return $return;
    // }

    /**
     * Get a configuration non-empty string value.
     *
     * @param string $name    Config key name.
     * @param string $default Default value to use.
     * @return string
     * @throws Exception If configuration value is invalid or doesn't exist.
     */
    private static function configNonEmptyString(string $name, string $default = ''): string
    {
        /** @var string $return */
        $return = self::configValue('string', $name, $default, \func_num_args() > 1);

        if ($return === '') {
            throw new Exception("Laravel config key '$name' is an empty string"); // todo - add custom exception
        }

        return $return;
    }



    /**
     * Check if a configuration value exists.
     *
     * @param string $name Config key name.
     * @return boolean
     */
    private static function configHas(string $name): mixed
    {
        $key = ServiceProvider::CONFIG_NAME . ".$name";

        /** @var Repository $config */
        $config = \app('config');

        return $config->has($key);
    }

    /**
     * Get a configuration array value.
     *
     * @param string  $method     The method to use to get the value.
     * @param string  $name       Config key name.
     * @param mixed   $default    Default value to use.
     * @param boolean $hasDefault Whether a default value was provided.
     * @return mixed
     * @throws InvalidArgumentException If configuration value does not exist or isn't a supported type.
     */
    private static function configValue(
        string $method,
        string $name,
        mixed $default = null,
        bool $hasDefault = false,
    ): mixed {

        $key = ServiceProvider::CONFIG_NAME . ".$name";

        /** @var Repository $config */
        $config = \app('config');

        // when there's no value present and no default is provided
        if (!$config->has($key) && !$hasDefault) {
            throw new InvalidArgumentException("Laravel config key '$key' not found"); // todo - add custom exception
        }

        $args = [$key];
        if ($hasDefault) {
            $args[] = $default;
        }

        return match ($method) {
            'array' => $config->array(...$args),
            'string' => $config->string(...$args),
            default => throw new InvalidArgumentException("Invalid method '$method'"), // todo - add custom exception
        };
    }
}
