<?php

namespace MoloniES\Controllers;

use MoloniES\API\Categories;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\DocumentError;

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
     *
     * @param string|null $name
     * @param int|null $parentId
     */
    public function __construct(?string $name = '', ?int $parentId = 0)
    {
        $this->name = trim($name);
        $this->parent_id = $parentId;
    }

    /**
     * Load by name
     *
     * @throws DocumentError
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

        try {
            $categoriesList = Categories::queryProductCategories($variables);
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error fetching categories','moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        }

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
     *
     * @throws DocumentError
     */
    public function create(): ProductCategory
    {
        try {
            $mutation = (Categories::mutationProductCategoryCreate($this->mapPropsToValues()))['data']['productCategoryCreate']['data'];
        } catch (APIExeption $e) {
            throw new DocumentError(
                sprintf(__('Error creating category %s','moloni_es') ,$this->name),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        }

        if (isset($mutation['productCategoryId'])) {
            $this->category_id = $mutation['productCategoryId'];

            return $this;
        }

        throw new DocumentError(
            sprintf(__('Error creating category %s','moloni_es') ,$this->name),
            [
                'mutation' => $mutation
            ]
        );
    }


    /**
     * Map this object properties to an array to insert/update a moloni product category
     *
     * @return array
     */
    private function mapPropsToValues(): array
    {
        return [
            'data' => [
                'name' => $this->name,
                'parentId' =>  $this->parent_id === 0 ? null : (int)$this->parent_id
            ]
        ];
    }
}
