<?php

namespace MoloniES\Helpers;

use MoloniES\API\Categories;
use MoloniES\Exceptions\Error;

class MoloniCategory
{
    /**
     * Gets category ancesters names
     *
     * @throws Error
     */
    public static function getFamlityTree(?int $moloniCategoryId = 0): array
    {
        /** Can happen because product can have no category in Moloni */
        if (empty($moloniCategoryId)) {
            return [];
        }

        $moloniId = $moloniCategoryId;
        $moloniCategoriesTree = [];
        $failsafe = 0;

        do {
            $query = (Categories::queryProductCategory(['productCategoryId' => (int)$moloniId]))['data']['productCategory']['data'];

            array_unshift($moloniCategoriesTree, $query['name']); //order needs to be inverted

            if ($query['parent'] === null) {
                break;
            }

            /** Next current id is this category parent */
            $moloniId = $query['parent']['productCategoryId'];

            $failsafe++;
        } while ($failsafe < 100);

        return $moloniCategoriesTree; //returns the names of all categories (from this product only)
    }
}