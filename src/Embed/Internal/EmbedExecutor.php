<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed\Internal;

use CodeDistortion\Backoff\Backoff;
use DigitalAnomaly\AlteredLogic\Adapters\Resolvers\HttpClientResolver;
use DigitalAnomaly\AlteredLogic\Common\Enums\AiProvidersEnum;
use DigitalAnomaly\AlteredLogic\Embed\DTOs\Transmission\EmbedTxnInputDTO;
use DigitalAnomaly\AlteredLogic\Embed\EmbedFaker;
use DigitalAnomaly\AlteredLogic\Embed\Internal\GatedBatch\EmbedGatedBatchInterface;
use DigitalAnomaly\AlteredLogic\Embed\Vector;
use DigitalAnomaly\AlteredLogic\Exceptions\EmbedException;
use DigitalAnomaly\AlteredLogic\Exceptions\ResourceException;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedApiClientInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Embed\EmbedModelInterface;
use DigitalAnomaly\AlteredLogic\Registry\Registry;
use DigitalAnomaly\AlteredLogic\Support\BatchHelper;
use DigitalAnomaly\AlteredLogic\Support\GatedBatch\Batch\GatedBatchInterface;
use DigitalAnomaly\AlteredLogic\Support\RetryHelper;

/**
 * Takes an EmbedGatedBatch and resolves the embedding vectors for it.
 */
final class EmbedExecutor
{
    /**
     * Process the batch.
     *
     * @param EmbedGatedBatchInterface&(GatedBatchInterface<EmbedGatedBatch,EmbedGatedBatchItem>|GatedBatchInterface<DocSearchableGatedBatch,DocSearchableGatedBatchItem>) $gatedBatch The batch to process.
     * @param boolean $force Whether or not to force the batch to run, regardless of the number of items.
     * @return void
     */
    public static function processBatch(EmbedGatedBatchInterface&GatedBatchInterface $gatedBatch, bool $force): void
    {
        if ($gatedBatch->embedModelProfile === null) {
            return;
        }

        if ($gatedBatch->isEmpty()) {
            return;
        }

        $resolved = self::executeEmbedProcess($gatedBatch, $force);

        $gatedBatch->pickAndRecordEmbeddingVectors($resolved);
    }

    /**
     * Execute the embed process.
     *
     * @param EmbedGatedBatchInterface $gatedBatch The batch to process.
     * @param boolean                  $force      Whether or not to force the batch to run, regardless of the number of
     *                                             items.
     * @return array<string,Vector> The embeddings, keyed by their source text.
     */
    public static function executeEmbedProcess(EmbedGatedBatchInterface $gatedBatch, bool $force): array
    {
        if ($gatedBatch->embedModelProfile === null) {
            return [];
        }

        $sources = $gatedBatch->pickUniqueSources();

        $batchSize = Registry::embedConfig()->deferBatchSize;
        if (!$force && (\count($sources) < $batchSize)) {
            return [];
        }

        $temp = $gatedBatch->embedModelProfile->buildEmbedApiClient($gatedBatch->credentialsOverride);
        ['apiClient' => $apiClient, 'embedModel' => $embedModel] = $temp;
        $connectionReference = EmbedConnectionReference::fromEmbedModel($embedModel, $gatedBatch->credentialsOverride);

        $faker = self::resolveFaker($gatedBatch, $embedModel);

        $cacheProfile = $gatedBatch->embedCacheProfile;
        $caches = $cacheProfile?->getCaches() ?? [];

        $dimensions = $embedModel->getDimensions();
        $tableSuffix = self::buildTableSuffix($embedModel->getModel());
        $debugLevel = $gatedBatch->debugLevel;

        $modelProvider = $embedModel->getProvider();
        $provider = $modelProvider instanceof AiProvidersEnum ? $modelProvider->value : $modelProvider;
        $overrideName = $gatedBatch->credentialsOverride?->pickCredentialsName($modelProvider);
        $modelCredentials = $embedModel->getCredentials();
        $credentialsName = $overrideName ?? ($modelCredentials instanceof AiProvidersEnum
            ? $modelCredentials->value
            : $modelCredentials);



        $resolved = self::applyFakes($gatedBatch, $faker, $embedModel);

        $debug = new EmbedExecutorDebug($debugLevel);
        $debug->showCredentialsDebug($credentialsName, $gatedBatch->credentialsOverride, $provider);
        $debug->showFakedEmbeddingsDebug($resolved);



        // fetch embeddings from the cache/s, if configured
        $unfulfilledKeysPerCache = [];
        $resolvedFromCache = [];
        foreach ($caches as $index => $cache) {

            // work out which embeddings are still missing
            $unresolved = \array_diff($sources, \array_keys($resolved));
            if (\count($unresolved) === 0) {
                break;
            }

            // try to get the embeddings from this cache
            try {

                // do it one batch-worth at a time
                $batches = BatchHelper::splitIntoBatches($unresolved, $batchSize, true, false);
                foreach ($batches as $batch) {

                    // todo - update the cache adapter that checks an external resourse to check if requests are blocked, and if so skip checking

                    $newEmbeddings = $cache->getEmbeddings($tableSuffix, $batch);
                    $newEmbeddings = \array_filter($newEmbeddings, fn($embedding) => $embedding !== null);

                    $resolvedFromCache = \array_merge($resolvedFromCache, $newEmbeddings);
                    $resolved = \array_merge($resolved, $newEmbeddings);
                }

            } catch (ResourceException) {
                // initialise the cache (create the table)
                // it didn't exist before, so don't bother retrying
                $cache->initialise($tableSuffix, $dimensions);
            }

            // record which embeddings are missing from this cache - so they can be populated later
            $unfulfilledKeysPerCache[$index] = \array_diff($unresolved, \array_keys($resolved));
        }

        $debug->showCachedEmbeddingsDebug($resolvedFromCache);



        // fetch outstanding embeddings from the API
        $unresolved = \array_diff($sources, \array_keys($resolved));

        if (\count($unresolved) > 0) {

            self::ensureHttpRequestsArentBlocked($embedModel->getModel());

            // do it one batch-worth at a time
            $batches = BatchHelper::splitIntoBatches($unresolved, $batchSize, $force, false);
            foreach ($batches as $batch) {

                $newEmbeddings = self::fetchFromApi($apiClient, $connectionReference, $batch, $debugLevel);
                $resolved = \array_merge($resolved, $newEmbeddings);
            }
        }



        // store missing embeddings in the relevant cache/s
        foreach ($caches as $index => $cache) {

            $unfulfilledKeys = $unfulfilledKeysPerCache[$index] ?? [];

            $newKeys = \array_values(\array_intersect($unfulfilledKeys, \array_keys($resolved)));
            $toAddToCache = \array_intersect_key($resolved, \array_flip($newKeys));

            // do it one batch-worth at a time
            $batches = BatchHelper::splitIntoBatches($toAddToCache, $batchSize, true, true);
            foreach ($batches as $batch) {

                // todo - update the cache adapter that stores in an external resourse to check if requests are blocked, and if so skip storing

                $work = fn() => $cache->storeEmbeddings($tableSuffix, $batch);
                $initialiseCache = fn() => $cache->initialise($tableSuffix, $dimensions);
                RetryHelper::embedCacheTry($work, $initialiseCache);
            }
        }



        return $resolved;
    }





    /**
     * Build a table suffix for the cache.
     *
     * (Just a common spot to keep this consistent).
     *
     * @param string $modelName The model name.
     * @return string
     */
    private static function buildTableSuffix(string $modelName): string
    {
        $modelName = (string) \preg_replace('/[^a-zA-Z0-9_]/', '_', $modelName);
        $modelName = \trim($modelName, '_');

        return "_{$modelName}";
    }





    /**
     * Resolve the faker to use.
     *
     * @param EmbedGatedBatchInterface $gatedBatch The batch to process.
     * @param EmbedModelInterface      $embedModel The embed model being used.
     * @return EmbedFaker|null
     */
    private static function resolveFaker(
        EmbedGatedBatchInterface $gatedBatch,
        EmbedModelInterface $embedModel,
    ): ?EmbedFaker {

        // gated sbatch level
        if ($gatedBatch->embedFaker !== null) {
            return $gatedBatch->embedFaker;
        }

        // model level
        if ($embedModel->getFaker() !== null) {
            return $embedModel->getFaker();
        }

        // global level
        return Registry::embedConfig()->faker;
    }

    /**
     * Get faked embeddings.
     *
     * @param EmbedGatedBatchInterface $gatedBatch The batch to process.
     * @param EmbedFaker|null          $faker      The faker to use.
     * @param EmbedModelInterface      $embedModel The already-resolved embed model (built with any override applied).
     * @return array<string,Vector> The embeddings, keyed by their source text.
     */
    private static function applyFakes(
        EmbedGatedBatchInterface $gatedBatch,
        ?EmbedFaker $faker,
        EmbedModelInterface $embedModel,
    ): array {

        if ($faker === null) {
            return [];
        }

        if ($gatedBatch->embedModelProfile === null) {
            return [];
        }

        // get dimensions from the already-resolved embed model (honours the credentials override)
        $dimensions = $embedModel->getDimensions();

        $sources = $gatedBatch->pickSources();

        $resolved = [];
        foreach ($sources as $source) {

            $vector = $faker->getVector($source, $dimensions);

            if ($vector !== null) {
                $resolved[$source] = $vector;
            }
        }

        return $resolved;
    }

    /**
     * Throw an exception if HTTP requests are blocked.
     *
     * @param string $modelName The name of the model that was being used (for the exception message).
     * @return void
     * @throws EmbedException If HTTP requests are blocked.
     */
    private static function ensureHttpRequestsArentBlocked(string $modelName): void
    {
        if (Registry::generalConfig()->blockRequests) {
            throw EmbedException::httpRequestsAreBlocked($modelName);
        }

        if (Registry::embedConfig()->blockRequests) {
            throw EmbedException::httpRequestsAreBlocked($modelName);
        }
    }





    /**
     * Send an embeddings request to the AI provider.
     *
     * Returns only the embeddings that were successfully generated.
     *
     * @param EmbedApiClientInterface  $apiClient           The API client to use.
     * @param EmbedConnectionReference $connectionReference Details about the connection used.
     * @param string[]                 $sources             The items to embed.
     * @param integer                  $debugLevel          The debug level to use.
     * @return array<string,Vector> The embeddings, keyed by their source text.
     */
    private static function fetchFromApi(
        EmbedApiClientInterface $apiClient,
        EmbedConnectionReference $connectionReference,
        array $sources,
        int $debugLevel,
    ): array {

        $embedInput = new EmbedTxnInputDTO($sources);

        $debug = new EmbedExecutorDebug($debugLevel);

        // todo - let the developer configure the backoff
        $backoff = Backoff::exponentialMs(5, 2)->immediateFirstRetry()->maxAttempts(4);
        $httpClient = HttpClientResolver::buildHttpClient($backoff);

        $requestBody = $apiClient->buildRequestBody($embedInput);
        $debug->showRequestBodyDebug($requestBody);
        $debug->showRequestSummaryDebug($embedInput);

        $httpTxn = $apiClient->sendRequest($httpClient, $requestBody);
        $embedTxn = $apiClient->buildResponse($embedInput, $httpTxn, $connectionReference);

        $responseBody = $httpTxn->response->body ?? '';
        $embeddings = self::pickSuccessfulEmbeddings($sources, $embedTxn->response->embeddings ?? []);
        $debug->showResponseBodyDebug($httpTxn, $embedTxn, $responseBody);
        $debug->showResponseSummaryDebug($embeddings, $httpTxn, $embedTxn);

        return $embeddings;
    }

    /**
     * Pick out the embeddings that were successfully generated.
     *
     * @param string[]                   $sources    The sources that were requested.
     * @param array<integer,Vector|null> $embeddings The embeddings to pick from.
     * @return array<string,Vector>
     */
    private static function pickSuccessfulEmbeddings(array $sources, array $embeddings): array
    {
        $return = [];
        $count = 0;
        foreach ($sources as $source) {
            $return[$source] = $embeddings[$count] ?? null;
            $count++;
        }

        return \array_filter($return, fn($embedding) => $embedding !== null);
    }
}
