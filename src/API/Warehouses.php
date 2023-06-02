<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Warehouses
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

        return Curl::complex('warehouses/warehouses', $query, $variables, 'warehouses');
    }
}
