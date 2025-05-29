<?php

namespace MoloniES\API\Abstracts;

use MoloniES\Exceptions\APIExeption;

abstract class EndpointAbstract
{
    /**
     * Save a request cache
     *
     * @var array
     */
    protected static $requestsCache = [];

    /**
     * Save a query to reduce I/O operations
     *
     * @var array
     */
    protected static $queryCache = [];

    /**
     * Load the query from a file
     *
     * @throws APIExeption
     */
    protected static function loadQuery(string $queryName): string
    {
        if (!empty(static::$queryCache[$queryName])) {
            return static::$queryCache[$queryName];
        }

        $queryPath = __DIR__ . '/Queries/' . $queryName . '.graphql';

        if (!file_exists($queryPath)) {
            throw new APIExeption("Query file not found: " . $queryPath);
        }

        self::$queryCache[$queryName] = file_get_contents($queryName);

        return self::$queryCache[$queryName];
    }

    /**
     * Load mutation from a file
     *
     * @throws APIExeption
     */
    protected static function loadMutation(string $mutationName): string
    {
        if (!empty(self::$queryCache[$mutationName])) {
            return self::$queryCache[$mutationName];
        }

        $mutationPath = __DIR__ . '/Mutations/' . $mutationName . '.graphql';

        if (!file_exists($mutationPath)) {
            throw new APIExeption("Mutation file not found: " . $mutationPath);
        }

        self::$queryCache[$mutationName] = file_get_contents($mutationPath);

        return self::$queryCache[$mutationName];
    }
}
