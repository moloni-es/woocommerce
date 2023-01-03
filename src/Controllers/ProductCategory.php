<?php

namespace MoloniES\Controllers;

use MoloniES\Error;
use MoloniES\API\Categories;

/**
 * Class Product Category
 * @package Moloni\Controllers
 */
class ProductCategory
{

    public $name;
    public $category_id;
    public $parent_id = 0;

    /**
     * Product Category constructor.
     * @param string $name
     * @param int $parentId
     */
    public function __construct($name, $parentId = 0)
    {
        $this->name = trim($name);
        $this->parent_id = $parentId;
    }

    /**
     * This method SHOULD be replaced by a productCategories/getBySearch
     * @throws Error
     */
    public function loadByName()
    {
        $variables = [
            'options' => [
                'filter' => [
                    'field' => 'parentId',
                    'comparison' => 'eq',
                    'value' => $this->parent_id === 0 ? null : (string)$this->parent_id
                ],
                'search' => [
                    'field' => "name",
                    'value' => $this->name
                ]
            ]
        ];

        $categoriesList = Categories::queryProductCategories($variables);
        if (!empty($categoriesList) && is_array($categoriesList)) {
            foreach ($categoriesList as $category) {
                if (strcmp((string)$category['name'], (string)$this->name) === 0) {
                    $this->category_id = $category['productCategoryId'];
                    return $this;
                }
            }
        }

        return false;
    }

    /**
     * Create a product based on a WooCommerce Product
     * @throws Error
     */
    public function create()
    {
        $insert = (Categories::mutationProductCategoryCreate($this->mapPropsToValues()))['data']['productCategoryCreate']['data'];

        if (isset($insert['productCategoryId'])) {
            $this->category_id = $insert['productCategoryId'];
            return $this;
        }

        throw new Error(sprintf(__('Error creating category %s','moloni_es') ,$this->name));
    }


    /**
     * Map this object properties to an array to insert/update a moloni product category
     * @return array
     */
    private function mapPropsToValues()
    {
        return [
            'data' => [
                'name' => $this->name,
                'parentId' =>  $this->parent_id === 0 ? null : (int)$this->parent_id
            ]
        ];
    }
}