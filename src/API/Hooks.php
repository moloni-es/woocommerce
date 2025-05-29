<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Hooks extends EndpointAbstract
{

    /**
     * Gets all hooks
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryHooks(?array $variables = []): array
    {
        $query = self::loadQuery('hooks');

        return Curl::complex('hooks', $query, $variables);
    }

    /**
     * Create a hook
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function mutationHookCreate(?array $variables = []): array
    {
        $query = self::loadMutation('hookCreate');

        return Curl::simple('hookCreate', $query, $variables);
    }

    /**
     * Delete hooks
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function mutationHookDelete(?array $variables = []): array
    {
        $query = self::loadMutation('hookDelete');

        return Curl::simple('hookDelete', $query, $variables);
    }
}
