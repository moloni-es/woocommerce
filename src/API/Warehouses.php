<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Warehouses extends EndpointAbstract
{
    /**
     * Get All Warehouses from Moloni ES
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryWarehouse(?array $variables = []): array
    {
        $query = self::loadQuery('warehouse');

        return Curl::simple('warehouse', $query, $variables);
    }

    /**
     * Get All Warehouses from Moloni ES
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryWarehouses(?array $variables = []): array
    {
        $action = 'warehouses';

        $query = self::loadQuery($action);

        if (empty(self::$requestsCache[$action])) {
            self::$requestsCache[$action] = Curl::complex($action, $query, $variables);
        }

        return self::$requestsCache[$action];
    }
}
