<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support;

use DigitalAnomaly\AlteredLogic\Exceptions\ResourceException;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocSearcherInterface;
use DigitalAnomaly\AlteredLogic\Interfaces\Documents\DocStoreInterface;

/**
 * Helper for retrying actions.
 */
final class RetryHelper
{
    /**
     * Perform an action with a DocStore. If a ResourceException is thrown, it will initialise the resource and try
     * again.
     *
     * @param callable          $work       The callable to try.
     * @param DocStoreInterface $docStore   The doc-store that is being used.
     * @param boolean           $allowRetry Whether to allow a retry if the document is not found.
     * @return mixed
     * @throws ResourceException If the necessary resources / tables don't exist (i.e. haven't been initialised).
     */
    public static function docStoreTry(
        callable $work,
        DocStoreInterface $docStore,
        bool $allowRetry = true,
    ): mixed {

        try {

            return $work();

        } catch (ResourceException $e) {

            if (!$allowRetry) {
                throw ResourceException::couldNotInitialiseResource($e);
            }

            $docStore->initialise();

            return self::docStoreTry($work, $docStore, false);
        }
    }



    /**
     * Perform an action with a DocSearcher. If a ResourceException is thrown, it will initialise the resource and try
     * again.
     *
     * @param callable             $work        The callable to try.
     * @param DocSearcherInterface $docSearcher The doc-searcher that is being used.
     * @param boolean              $allowRetry  Whether to allow a retry if the document is not found.
     * @return mixed
     * @throws ResourceException If the necessary resources / tables don't exist (i.e. haven't been initialised).
     */
    public static function docSearcherTry(
        callable $work,
        DocSearcherInterface $docSearcher,
        bool $allowRetry = true,
    ): mixed {

        try {

            return $work();

        } catch (ResourceException $e) {

            if (!$allowRetry) {
                throw ResourceException::couldNotInitialiseResource($e);
            }

            $docSearcher->initialise();

            return self::docSearcherTry($work, $docSearcher, false);
        }
    }



    /**
     * Perform an action. If it detects a ResourceException, it will initialise the resource and try again.
     *
     * @param callable $work            The callable to try.
     * @param callable $initialiseCache The callback to use to initialise the cache.
     * @param boolean  $allowRetry      Whether to allow a retry if the document is not found.
     * @return mixed
     * @throws ResourceException If the necessary resources / tables don't exist (i.e. haven't been initialised).
     */
    public static function embedCacheTry(
        callable $work,
        callable $initialiseCache,
        bool $allowRetry = true,
    ): mixed {

        try {

            return $work();

        } catch (ResourceException $e) {

            if (!$allowRetry) {
                throw ResourceException::couldNotInitialiseResource($e);
            }

            $initialiseCache();

            return self::embedCacheTry($work, $initialiseCache, false);
        }
    }
}
