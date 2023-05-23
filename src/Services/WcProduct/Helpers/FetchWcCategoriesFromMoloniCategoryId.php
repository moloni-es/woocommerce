<?php

namespace MoloniES\Services\WcProduct\Helpers;

use MoloniES\Exceptions\Error;
use MoloniES\Helpers\MoloniCategory;

class FetchWcCategoriesFromMoloniCategoryId
{
    private $categoryId;

    public function __construct(?int $categoryId = 0)
    {
        $this->categoryId = $categoryId;
    }

    public function get(): array
    {
        try {
            $namesArray = MoloniCategory::getFamlityTree($this->categoryId);
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
}