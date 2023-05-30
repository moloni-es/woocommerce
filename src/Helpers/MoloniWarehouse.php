<?php

namespace MoloniES\Helpers;

use MoloniES\API\Warehouses;

class MoloniWarehouse
{
    public static function getDefaultWarehouse()
    {
        $warehouseId = 0;

        $results = Warehouses::queryWarehouses();

        foreach ($results as $result) {
            if ((bool)$result['isDefault'] === true) {
                $warehouseId = (int)$result['warehouseId'];

                break;
            }
        }

        if ($warehouseId === 0) {
            // todo: throw error
        }

        return $warehouseId;
    }
}