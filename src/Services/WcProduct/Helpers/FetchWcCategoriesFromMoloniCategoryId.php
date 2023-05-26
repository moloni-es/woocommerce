<?php

namespace MoloniES\Services\WcProduct\Helpers;

use MoloniES\API\Categories;
use MoloniES\Exceptions\Error;

class FetchWcCategoriesFromMoloniCategoryId
{
    private $categoryId;

    public function __construct(?int $categoryId = 0)
    {
        $this->categoryId = (int)$categoryId;
    }

    public function get(): array
    {
        /** Can happen because product can have no category in Moloni */
        if (empty($this->categoryId)) {
            return [];
        }

        try {
            $namesArray = $this->getFamlityTree();
        } catch (Error $e) {
            return [];
        }

        $parentId = 0;
        $categoriesIds = [];

        foreach ($namesArray as $prod_cat) {
            $existingTerm = term_exists($prod_cat, 'product_cat', $parentId);

            if (!$existingTerm) {
                $newTerm = wp_insert_term($prod_cat, 'product_cat', ['parent' => $parentId]);
                $parentId = $newTerm['term_id'];

                array_unshift($categoriesIds, $newTerm['term_id']);
            } else {
                $parentId = $existingTerm['term_id'];

                array_unshift($categoriesIds, $existingTerm['term_id']);
            }
        }

        return $categoriesIds;
    }

    /**
     * Gets category ancesters names
     *
     * @throws Error
     */
    private function getFamlityTree(): array
    {
        $moloniId = $this->categoryId;
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