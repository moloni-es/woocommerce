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
        $query = 'mutation stockMovementManualEntryCreate($companyId: Int!,$data: StockMovementManualInsert!)
        {
            stockMovementManualEntryCreate(companyId: $companyId,data: $data)
            {
                data{
                    stockMovementId
                    type
                    direction
                    qty
                }
                errors{
                    field
                    msg
                }
            }
        }';

        return Curl::simple('stocks/stockMovementManualEntryCreate', $query, $variables);
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
        $query = 'mutation stockMovementManualExitCreate($companyId: Int!,$data: StockMovementManualInsert!)
        {
            stockMovementManualExitCreate(companyId: $companyId,data: $data)
            {
                data{
                    stockMovementId
                    type
                    direction
                    qty
                }
                errors{
                    field
                    msg
                }
            }
        }';

        return Curl::simple('stocks/stockMovementManualExitCreate', $query, $variables);
    }
}
