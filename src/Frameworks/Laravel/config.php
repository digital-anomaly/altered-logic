<?php

declare(strict_types=1);

use DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Embed\OpenAiEmbedApiClient;
use DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Embed\OpenAiEmbedModel;
use DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Modex\OpenAiModexModel;
use DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Modex\OpenAiResponsesApiClient;
use DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\OpenAiCredentials;
use DigitalAnomaly\AlteredLogic\Adapters\DocSearchers\Laravel\LaravelPostgresEmbedDocSearcher;
use DigitalAnomaly\AlteredLogic\Adapters\DocStores\Laravel\LaravelMariaDBDocStore;
use DigitalAnomaly\AlteredLogic\Adapters\DocStores\Laravel\LaravelMySQLDocStore;
use DigitalAnomaly\AlteredLogic\Adapters\EmbedCaches\Laravel\LaravelMariaDBEmbedCache;
use DigitalAnomaly\AlteredLogic\Adapters\EmbedCaches\Laravel\LaravelMySQLEmbedCache;
use DigitalAnomaly\AlteredLogic\Adapters\EmbedCaches\Laravel\LaravelPostgresEmbedCache;

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider Credentials
    |--------------------------------------------------------------------------
    |
    | These are your AI provider credentials.
    |
    */

    'credentials' => [

        'openai' => [
            'type' => OpenAiCredentials::class,
            'api_key' => env('OPENAI_API_KEY'),
            'organisation' => env('OPENAI_ORGANISATION', env('OPENAI_ORGANIZATION')),
            'project_id' => env('OPENAI_PROJECT_ID'),
        ],
    ],





    /*
    |--------------------------------------------------------------------------
    | Default Profiles
    |--------------------------------------------------------------------------
    |
    | These are the default profiles to use for each feature. The profiles
    | themselves are defined below.
    |
    */

    'default_profiles' => [
        'embed_model_profile' => 'embed_model_profile_1',
        'embed_cache_profile' => 'embed_cache_profile_1',
        'doc_profile' => 'doc_profile_1',
        'modex_model_profile' => 'modex_profile_1',
    ],





    /*
    |--------------------------------------------------------------------------
    | Embedding Model Profiles
    |--------------------------------------------------------------------------
    |
    | These profiles define which embedding model/s to use when generating
    | embeddings.
    |
    */

    'embed_model_profiles' => [

        'embed_model_profile_1' => [
            'models' => ['openai_text_embedding_3_small'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Embeddding Models
    |--------------------------------------------------------------------------
    |
    | These are the embedding models available to use when generating
    | embeddings.
    |
    */

    'embed_models' => [

        'openai_text_embedding_3_small' => [
            'type' => OpenAiEmbedModel::class,
            'credentials' => 'openai',
            'client' => OpenAiEmbedApiClient::class,
            'url' => env('OPENAI_EMBEDDINGS_URL', 'https://api.openai.com/v1/embeddings'),
            'custom_headers' => ['hello' => 'world'],
            'model' => 'text-embedding-3-small',
            'dimensions' => null,
        ],
    ],





    /*
    |--------------------------------------------------------------------------
    | Embedding Cache Profiles
    |--------------------------------------------------------------------------
    |
    | These profiles define which embedding cache/s to use when generating
    | embeddings.
    |
    */

    'embed_cache_profiles' => [

        'embed_cache_profile_1' => [
            'caches' => ['postgres_embed_cache'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedding Caches
    |--------------------------------------------------------------------------
    |
    | These are the embedding caches available to use when generating
    | embeddings.
    |
    */

    'embed_caches' => [

        'mysql_embed_cache' => [
            'type' => LaravelMySQLEmbedCache::class,
            'database_connection' => 'mysql',
            'table_prefix' => 'embed_cache_',
        ],

        'mariadb_embed_cache' => [
            'type' => LaravelMariaDBEmbedCache::class,
            'database_connection' => 'mariadb',
            'table_prefix' => 'embed_cache_',
        ],

        'postgres_embed_cache' => [
            'type' => LaravelPostgresEmbedCache::class,
            'database_connection' => 'pgsql',
            'table_prefix' => 'embed_cache_',
        ],
    ],





    /*
    |--------------------------------------------------------------------------
    | Document Profiles
    |--------------------------------------------------------------------------
    |
    | These profiles define which document store/s to use when storing and
    | searching documents.
    |
    */

    'doc_profiles' => [

        'doc_profile_1' => [
            'store' => 'mysql_doc_store',
            'searchers' => ['postgres_doc_searcher'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Stores
    |--------------------------------------------------------------------------
    |
    | These are the stores available to use when storing and retrieving
    | documents and their metadata.
    |
    */

    'doc_stores' => [

        'mysql_doc_store' => [
            'type' => LaravelMySQLDocStore::class,
            'database_connection' => 'mysql',
            'table' => 'documents',
        ],

        'mariadb_doc_store' => [
            'type' => LaravelMariaDBDocStore::class,
            'database_connection' => 'mariadb',
            'table' => 'documents',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Searchers
    |--------------------------------------------------------------------------
    |
    | These are the available locations where document search-information
    | is stored and searched against.
    |
    */

    'doc_searchers' => [

        'postgres_doc_searcher' => [
            'type' => LaravelPostgresEmbedDocSearcher::class,
            'database_connection' => 'pgsql',
            'table' => 'doc_searchable_my_documents',
            'embed_model_profile' => 'embed_model_profile_1',
        ],
    ],





    /*
    |--------------------------------------------------------------------------
    | Modex Profiles
    |--------------------------------------------------------------------------
    |
    | These profiles define which LLM model/s to use when generating
    | responses.
    |
    */

    'modex_profiles' => [

        'modex_profile_1' => [
            'models' => ['openai_modex_model'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Modex Connections
    |--------------------------------------------------------------------------
    |
    | These are the available models to use when interacting with LLMs.
    |
    */

    'modex_models' => [

        'openai_modex_model' => [
            'type' => OpenAiModexModel::class,
            'credentials' => 'openai',
            'client' => OpenAiResponsesApiClient::class,
            'url' => env('OPENAI_RESPONSES_URL', 'https://api.openai.com/v1/responses'),
            'custom_headers' => ['hello' => 'world'],
            'model' => 'gpt-5-mini',
        ],
    ],





    /*
    |--------------------------------------------------------------------------
    | Debug Levels
    |--------------------------------------------------------------------------
    |
    | These define the debug level for each feature.
    |
    | 0 = off
    | 1 = basic
    | 2 = verbose
    |
    */

    'debug_levels' => [
        'embeddings' => 0,
        'documents' => 0,
        'modex' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Sizes
    |--------------------------------------------------------------------------
    |
    | These define how big each batch shall become before it's processed.
    | These are used by DocStoreDefer and EmbedDefer.
    |
    */

    'batch_sizes' => [
        'embeddings' => 100,
        'documents' => 100,
    ],
];
