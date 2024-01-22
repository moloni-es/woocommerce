<?php

namespace MoloniES\Helpers;

use MoloniES\API\Warehouses;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\HelperException;

class MoloniWarehouse
{
    /**
     * Get company default warehouse ID
     *
     * @return int
     *
     * @throws HelperException
     */
    public static function getDefaultWarehouseId(): int
    {
        try {
            $results = Warehouses::queryWarehouses();
        } catch (APIExeption $e) {
            throw new HelperException(
                __('Error fetching warehouses', 'moloni_es'),
                ['message' => $e->getMessage(), 'data' => $e->getData()]
            );
        }

        foreach ($results as $result) {
            if ((bool)$result['isDefault'] === true) {
                return (int)$result['warehouseId'];
            }
        }

        throw new HelperException(
            __('No default warehouse found', 'moloni_es'),
            ['results' => $results]
        );
    }
}
