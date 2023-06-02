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
    public static function getDefaultWarehouse(): int
    {
        $warehouseId = 0;

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
                $warehouseId = (int)$result['warehouseId'];

                break;
            }
        }

        if ($warehouseId === 0) {
            throw new HelperException(
                __('No default warehouse found', 'moloni_es'),
                ['results' => $results]
            );
        }

        return $warehouseId;
    }
}
