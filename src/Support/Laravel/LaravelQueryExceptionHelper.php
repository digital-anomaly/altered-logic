<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Laravel;

use DigitalAnomaly\AlteredLogic\Exceptions\ResourceException;
use Illuminate\Database\QueryException;

/**
 * Helper class for Laravel-specific QueryException exception handling.
 */
final class LaravelQueryExceptionHelper
{
    /**
     * If the QueryException was thrown because the table doesn't exist, wrap it in a ResourceException.
     *
     * @param QueryException $e The query exception to wrap.
     * @return QueryException|ResourceException
     */
    public static function wrapResourceException(QueryException $e): QueryException|ResourceException
    {
        // MySQL and MariaDB
        // code "42S02" means the table doesn't exist
        // https://mariadb.com/kb/en/sqlstate/

        if ($e->getCode() === '42S02') {
            return ResourceException::resourceDoesNotExist($e);
        }

        // Postgres
        // code "42P01" means the table doesn't exist
        // https://www.postgresql.org/docs/current/errcodes-appendix.html

        if ($e->getCode() === '42P01') {
            return ResourceException::resourceDoesNotExist($e);
        }

        // (other references, for another day)
        // https://learn.microsoft.com/en-us/sql/odbc/reference/appendixes/appendix-a-odbc-error-codes?view=sql-server-ver15

        // …



        return $e;
    }

    /**
     * Run the given callback. Throws a ResourceException if a table doesn't exist.
     *
     * @param callable $callback The callback to run.
     * @return mixed The return value of the callback.
     * @throws ResourceException If the callback throws a QueryException.
     */
    public static function runWrapped(callable $callback): mixed
    {
        try {

            return $callback();

        } catch (QueryException $e) {
            throw self::wrapResourceException($e);
        }
    }
}
