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

    private const string CONFIG_PREFIX__DEFAULTS = 'defaults';

    private const string CONFIG_PREFIX__EMBED_MODEL_PROFILES = 'embed_model_profiles';
    private const string CONFIG_PREFIX__EMBED_MODELS = 'embed_models';

    private const string CONFIG_PREFIX__EMBED_CACHE_PROFILES = 'embed_cache_profiles';
    private const string CONFIG_PREFIX__EMBED_CACHES = 'embed_caches';

    private const string CONFIG_PREFIX__DOC_PROFILES = 'doc_profiles';
    private const string CONFIG_PREFIX__DOC_STORES = 'doc_stores';
    private const string CONFIG_PREFIX__DOC_SEARCHERS = 'doc_searchers';

    private const string CONFIG_PREFIX__MODEX_PROFILES = 'modex_model_profiles';
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
     * @param boolean $checkExists Whether to check if the profile has been defined in the configuration when set.
     * @return string|null
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultEmbedModelProfileName(bool $checkExists): ?string
    {
        $defaultsPrefix = self::CONFIG_PREFIX__DEFAULTS;
        $profilePrefix = self::CONFIG_PREFIX__EMBED_MODEL_PROFILES;

        $profileName = self::configStringOrNullOrMissing("{$defaultsPrefix}.embed_model_or_profile");

        // when empty
        if (\in_array($profileName, [null, ''], true)) {
            return null;
        }

        // check this profile has been defined (if the user requested)
        if ($checkExists && !self::configHas("{$profilePrefix}.{$profileName}")) {
            return null;
        }

        return $profileName;
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

        // only proceed if the user has defined configuration for this
        if (!self::configHas("{$profilePrefix}.{$name}.models")) {
            return null;
        }

        // check that the embed model names look valid
        $modelNames = self::configNonEmptyArray("{$profilePrefix}.{$name}.models");
        foreach ($modelNames as $modelName) {

            // can't resolve the model name
            if (!\is_string($modelName)) {
                $modelName = (string) $modelName;
                throw new Exception("Invalid embed model name: $modelName"); // todo - add custom exception
            }
        }

        /** @var string[] $modelNames */
        return new EmbedModelProfile()->addModels($modelNames);
    }



    /**
     * Get the default embed model name from configuration.
     *
     * @param boolean $checkExists Whether to check if the model has been defined in the configuration when set.
     * @return string|null
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultEmbedModelName(bool $checkExists): ?string
    {
        $defaultsPrefix = self::CONFIG_PREFIX__DEFAULTS;
        $modelPrefix = self::CONFIG_PREFIX__EMBED_MODELS;

        $modelName = self::configStringOrNullOrMissing("{$defaultsPrefix}.embed_model_or_profile");

        // when empty
        if (\in_array($modelName, [null, ''], true)) {
            return null;
        }

        // check this model has been defined if( the user requested)
        if ($checkExists && !self::configHas("{$modelPrefix}.{$modelName}")) {
            return null;
        }

        return $modelName;
    }

    /**
     * Build an EmbedModel from configuration.
     *
     * @param string $name The name of the model to build.
     * @return EmbedModelInterface|null
     * @throws Exception If the model could not be built.
     */
    public static function buildEmbedModel(string $name): ?EmbedModelInterface
    {
        $embedModelsPrefix = self::CONFIG_PREFIX__EMBED_MODELS;

        // only proceed if the user has defined configuration for this
        if (!self::configHas("{$embedModelsPrefix}.{$name}")) {
            return null;
        }

        /** @var EmbedModelInterface|null $model */
        $model = self::buildInstance(
            $name,
            "{$embedModelsPrefix}.{$name}",
            EmbedModelInterface::class,
        );

        if ($model === null) {
            throw new Exception("Embed model '$name' could not be built"); // todo - add custom exception
        }

        return $model;
    }





    /**
     * Get the default embed cache profile name from configuration.
     *
     * @param boolean $checkExists Whether to check if the profile has been defined in the configuration when set.
     * @return string|null
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultEmbedCacheProfileName(bool $checkExists): ?string
    {
        $defaultsPrefix = self::CONFIG_PREFIX__DEFAULTS;
        $profilePrefix = self::CONFIG_PREFIX__EMBED_CACHE_PROFILES;

        $profileName = self::configStringOrNullOrMissing("{$defaultsPrefix}.embed_cache_or_profile");

        // when empty
        if (\in_array($profileName, [null, ''], true)) {
            return null;
        }

        // check this profile has been defined (if the user requested)
        if ($checkExists && !self::configHas("{$profilePrefix}.{$profileName}")) {
            return null;
        }

        return $profileName;
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

        // only proceed if the user has defined configuration for this
        if (!self::configHas("{$profilePrefix}.{$name}.caches")) {
            return null;
        }

        // check that the embed cache names look valid
        $cacheNames = self::configNonEmptyArray("{$profilePrefix}.{$name}.caches");
        foreach ($cacheNames as $cacheName) {

            // can't resolve the cache name
            if (!\is_string($cacheName)) {
                $cacheName = (string) $cacheName;
                throw new Exception("Invalid embed cache name: $cacheName"); // todo - add custom exception
            }
        }

        /** @var string[] $cacheNames */
        return (new EmbedCacheProfile())->addCaches($cacheNames);
    }



    /**
     * Get the default embed cache name from configuration.
     *
     * @param boolean $checkExists Whether to check if the cache has been defined in the configuration when set.
     * @return string|null
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultEmbedCacheName(bool $checkExists): ?string
    {
        $defaultsPrefix = self::CONFIG_PREFIX__DEFAULTS;
        $cachePrefix = self::CONFIG_PREFIX__EMBED_CACHES;

        $cacheName = self::configStringOrNullOrMissing("{$defaultsPrefix}.embed_cache_or_profile");

        // when empty
        if (\in_array($cacheName, [null, ''], true)) {
            return null;
        }

        // check this cache has been defined if( the user requested)
        if ($checkExists && !self::configHas("{$cachePrefix}.{$cacheName}")) {
            return null;
        }

        return $cacheName;
    }

    /**
     * Build an EmbedCache from configuration.
     *
     * @param string $name The name of the cache to build.
     * @return EmbedCacheInterface|null
     * @throws Exception If the cache could not be built.
     */
    public static function buildEmbedCache(string $name): ?EmbedCacheInterface
    {
        $embedCachesPrefix = self::CONFIG_PREFIX__EMBED_CACHES;

        // only proceed if the user has defined configuration for this
        if (!self::configHas("{$embedCachesPrefix}.{$name}")) {
            return null;
        }

        /** @var EmbedCacheInterface|null $cache */
        $cache = self::buildInstance(
            $name,
            "{$embedCachesPrefix}.{$name}",
            EmbedCacheInterface::class,
        );

        if ($cache === null) {
            throw new Exception("Embed cache '$name' could not be built"); // todo - add custom exception
        }

        return $cache;
    }





    /**
     * Get the default document profile name from configuration.
     *
     * @param boolean $checkExists Whether to check if the profile has been defined in the configuration when set.
     * @return string|null
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultDocumentProfileName(bool $checkExists): ?string
    {
        $defaultsPrefix = self::CONFIG_PREFIX__DEFAULTS;
        $profilePrefix = self::CONFIG_PREFIX__DOC_PROFILES;

        $profileName = self::configStringOrNullOrMissing("{$defaultsPrefix}.doc_profile");

        // when empty
        if (\in_array($profileName, [null, ''], true)) {
            return null;
        }

        // check this profile has been defined (if the user requested)
        if ($checkExists && !self::configHas("{$profilePrefix}.{$profileName}")) {
            return null;
        }

        return $profileName;
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

        // only proceed if the user has defined configuration for these
        if (!self::configHas("{$profilePrefix}.{$name}.store")) {
            return null;
        }
        if (!self::configHas("{$profilePrefix}.{$name}.searchers")) {
            return null;
        }

        $docStoreName = self::configNonEmptyString("{$profilePrefix}.{$name}.store");

        // create the profile and set the store name
        $profile = new DocumentProfile();
        $profile->setStore($docStoreName);

        // check that the doc-searcher names look valid
        $searcherNames = self::configNonEmptyArray("{$profilePrefix}.{$name}.searchers");
        foreach ($searcherNames as $searcherName) {

            // can't resolve the searcher name
            if (!\is_string($searcherName)) {
                $searcherName = (string) $searcherName;
                throw new Exception("Invalid doc searcher name: $searcherName"); // todo - add custom exception
            }

            $profile->attachSearcher($searcherName);
        }

        return $profile;
    }



    /**
     * Build a DocStore from configuration.
     *
     * @param string $name The name of the doc-store to build.
     * @return DocStoreInterface|null
     * @throws Exception If the doc-store could not be built.
     */
    public static function buildDocStore(string $name): ?DocStoreInterface
    {
        $docStoresPrefix = self::CONFIG_PREFIX__DOC_STORES;

        // only proceed if the user has defined configuration for this
        if (!self::configHas("{$docStoresPrefix}.{$name}")) {
            return null;
        }

        /** @var DocStoreInterface|null $docStore */
        $docStore = self::buildInstance(
            $name,
            "{$docStoresPrefix}.{$name}",
            DocStoreInterface::class,
        );

        if ($docStore === null) {
            throw new Exception("Doc store '$name' could not be built"); // todo - add custom exception
        }

        return $docStore;
    }

    /**
     * Build a DocSearcher from configuration.
     *
     * @param string $name The name of the doc-searcher to build.
     * @return DocSearcherInterface|null
     * @throws Exception If the doc-searcher could not be built.
     */
    public static function buildDocSearcher(string $name): ?DocSearcherInterface
    {
        $docSearchersPrefix = self::CONFIG_PREFIX__DOC_SEARCHERS;

        // only proceed if the user has defined configuration for this
        if (!self::configHas("{$docSearchersPrefix}.{$name}")) {
            return null;
        }

        /** @var DocSearcherInterface|null $docSearcher */
        $docSearcher = self::buildInstance(
            $name,
            "{$docSearchersPrefix}.{$name}",
            DocSearcherInterface::class,
        );

        if ($docSearcher === null) {
            throw new Exception("Doc searcher '$name' could not be built"); // todo - add custom exception
        }

        return $docSearcher;
    }





    /**
     * Get the default modex model profile name from configuration.
     *
     * @param boolean $checkExists Whether to check if the profile has been defined in the configuration when set.
     * @return string|null
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultModexModelProfileName(bool $checkExists): ?string
    {
        $defaultsPrefix = self::CONFIG_PREFIX__DEFAULTS;
        $profilePrefix = self::CONFIG_PREFIX__MODEX_PROFILES;

        $profileName = self::configStringOrNullOrMissing("{$defaultsPrefix}.modex_model_or_profile");

        // when empty
        if (\in_array($profileName, [null, ''], true)) {
            return null;
        }

        // check this profile has been defined (if the user requested)
        if ($checkExists && !self::configHas("{$profilePrefix}.{$profileName}")) {
            return null;
        }

        return $profileName;
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

        // only proceed if the user has defined configuration for this
        if (!self::configHas("{$profilePrefix}.{$name}.models")) {
            return null;
        }

        // check that the modex model names look valid
        $modelNames = self::configNonEmptyArray("{$profilePrefix}.{$name}.models");
        foreach ($modelNames as $modelName) {

            // can't resolve the model name
            if (!\is_string($modelName)) {
                $modelName = (string) $modelName;
                throw new Exception("Invalid modex model name: $modelName"); // todo - add custom exception
            }
        }

        /** @var string[] $modelNames */
        return new ModexModelProfile()->addModels($modelNames);
    }





    /**
     * Get the default modex model name from configuration.
     *
     * @param boolean $checkExists Whether to check if the model has been defined in the configuration when set.
     * @return string|null
     * @throws Exception If configuration value is missing or empty.
     */
    public static function getDefaultModexModelName(bool $checkExists): ?string
    {
        $defaultsPrefix = self::CONFIG_PREFIX__DEFAULTS;
        $modelPrefix = self::CONFIG_PREFIX__MODEX_MODELS;

        $modelName = self::configStringOrNullOrMissing("{$defaultsPrefix}.modex_model_or_profile");

        // when empty
        if (\in_array($modelName, [null, ''], true)) {
            return null;
        }

        // check this model has been defined if( the user requested)
        if ($checkExists && !self::configHas("{$modelPrefix}.{$modelName}")) {
            return null;
        }

        return $modelName;
    }

    /**
     * Build a ModexModel from configuration.
     *
     * @param string $name The name of the model to build.
     * @return ModexModelInterface|null
     * @throws Exception If the model could not be built.
     */
    public static function buildModexModel(string $name): ?ModexModelInterface
    {
        $modexModelsPrefix = self::CONFIG_PREFIX__MODEX_MODELS;

        // only proceed if the user has defined configuration for this
        if (!self::configHas("{$modexModelsPrefix}.{$name}")) {
            return null;
        }

        /** @var ModexModelInterface|null $model */
        $model = self::buildInstance(
            $name,
            "{$modexModelsPrefix}.{$name}",
            ModexModelInterface::class,
        );

        if ($model === null) {
            throw new Exception("Modex model '$name' could not be built"); // todo - add custom exception
        }

        return $model;
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
     * Get a configuration string value (empty string / null / missig is allowed).
     *
     * @param string $name    Config key name.
     * @param string $default Default value to use.
     * @return string|null
     * @throws Exception If configuration value is invalid or doesn't exist.
     */
    private static function configStringOrNullOrMissing(string $name, string $default = ''): ?string
    {
        /** @var string|null $return */
        $return = self::configValue(
            'string',
            $name,
            $default,
            \func_num_args() > 1,
            true,
            true,
        );

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
     * @param string  $method       The method to use to get the value.
     * @param string  $name         Config key name.
     * @param mixed   $default      Default value to use.
     * @param boolean $hasDefault   Whether a default value was provided.
     * @param boolean $allowNull    Whether to allow the value to be null.
     * @param boolean $allowMissing Whether to allow the value to be missing.
     * @return mixed
     * @throws InvalidArgumentException If configuration value does not exist or isn't a supported type.
     */
    public static function configValue(
        string $method,
        string $name,
        mixed $default = null,
        bool $hasDefault = false,
        bool $allowNull = false,
        bool $allowMissing = false,
    ): mixed {

        $key = ServiceProvider::CONFIG_NAME . ".$name";

        /** @var Repository $config */
        $config = \app('config');

        // when there's no value present
        if (!$config->has($key)) {

            if ($hasDefault) {
                return $default;
            }

            if ($allowMissing && $allowNull) {
                return null;
            }

            throw new InvalidArgumentException("Laravel config key '$key' not found"); // todo - add custom exception
        }

        // check for null
        if ($config->get($key, null) === null) {
            if ($allowNull) {
                return null;
            }
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
