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
    public static function queryWarehouses(?array $variables = []): array
    {
        $action = 'warehouses/warehouses';

        $query = 'query warehouses($companyId: Int!,$options: WarehouseOptions)
        {
            warehouses(companyId: $companyId, options: $options) 
            {
                errors
                {
                    field
                    msg
                }
                options
                {
                    pagination
                    {
                        page
                        qty
                        count
                    }
                }
                data
                {
                    warehouseId
                    name
                    number
                    isDefault
                }
            }
        }';

        if (empty(self::$cache[$action])) {
            self::$cache[$action] = Curl::complex($action, $query, $variables, 'warehouses');
        }

        return self::$cache[$action];
    }
}
