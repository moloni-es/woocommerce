<?php

namespace MoloniES\Helpers;

class MoloniProduct
{
    public static function parseMoloniStock(array $moloniProduct, int $warehouseId): float
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

    public static function parseVariantAttributes(array $moloniVariant): array
    {
        $attributes = [];

        foreach ($moloniVariant["propertyPairs"] as $value) {
            $propertyName = trim($value['property']["name"]);
            $propertyValue = trim($value['propertyValue']["value"]);

            $attributes[sanitize_title($propertyName)] = $propertyValue;
        }

        return $attributes;
    }

    public static function parseParentVariantsAttributes(array $moloniProduct): array
    {
        $attributes = [];

        foreach ($moloniProduct['variants'] as $variant) {
            foreach ($variant['propertyPairs'] as $property) {
                $propertyName = trim($property['property']['name']);
                $propertyValue = trim($property['propertyValue']['value']);

                if (!isset($attributes[$propertyName])) {
                    $attributes[$propertyName] = [];
                }

                if (!in_array($propertyValue, $attributes[$propertyName], true)) {
                    $attributes[$propertyName][] = $propertyValue;
                }
            }
        }

        return $attributes;
    }
}