<?php

namespace MoloniES\Helpers;

class MoloniProduct
{
    public static function ParseMoloniStock($moloniProduct, $warehouseId): float
    {
        $stock = 0.0;

        if ($warehouseId === 1) {
            $stock = (float)($moloniProduct['stock'] ?? 0);
        } else {
            foreach ($moloniProduct['warehouses'] as $warehouse) {
                $stock = (float)$warehouse['stock'];

                if ((int)$warehouse['warehouseId'] === $warehouseId) {
                    break;
                }
            }
        }

        return $stock;
    }
}