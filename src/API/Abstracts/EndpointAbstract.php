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
        return self::loadFromFile('Queries', $queryName);
    }

    /**
     * Load mutation from a file
     *
     * @throws APIExeption
     */
    protected static function loadMutation(string $mutationName): string
    {
        return self::loadFromFile('Mutations', $mutationName);
    }

    /**
     * Load mutation or query from a file
     *
     * @throws APIExeption
     */
    private static function loadFromFile($folder, $name): string
    {
        $path = MOLONI_ES_DIR . "/src/API/$folder/$name.graphql";

        if (!file_exists($path)) {
            throw new APIExeption("Query/Mutation file not found: $path");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            $error = error_get_last();

            throw new APIExeption("Query/Mutation file failed to read: {$error['message']}");
        }

        self::$queryCache[$name] = $contents;

        return $contents;
    }
}
