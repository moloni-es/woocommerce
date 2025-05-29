<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Stocks extends EndpointAbstract
{
    /**
     * Adds stock to a product
     *
     * @param array $variables variables of the query
     *
     * @return array returns info about the movement
     *
     * @throws APIExeption
     */
    public static function mutationStockMovementManualEntryCreate(array $variables = []): array
    {
        $query = self::loadMutation('stockMovementManualEntryCreate');

        return Curl::simple('stockMovementManualEntryCreate', $query, $variables);
    }

    /**
     * Removes stock from a product
     *
     * @param array $variables variables of the query
     *
     * @return array returns info about the movement
     *
     * @throws APIExeption
     */
    public static function mutationStockMovementManualExitCreate(array $variables = []): array
    {
        $query = self::loadMutation('stockMovementManualExitCreate');

        return Curl::simple('stockMovementManualExitCreate', $query, $variables);
    }
}
