<?php

namespace MoloniES\Services\MoloniProduct\Helpers;

use MoloniES\API\Categories;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\HelperException;

class GetOrCreateCategory
{
    private $name;
    private $parent_id;

    public function __construct(?string $name = '', ?int $parentId = 0)
    {
        $this->name = trim($name);
        $this->parent_id = $parentId;
    }

    /**
     * Getter
     *
     * @throws HelperException
     */
    public function get(): int
    {
        $categoryId = $this->fetchByName();

        if ($categoryId === 0) {
            $categoryId = $this->createCategory();
        }

        return $categoryId;
    }

    //          Privates          //

    /**
     * Fetch category by name
     *
     * @throws HelperException
     */
    private function fetchByName(): int
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
            throw new HelperException(
                __('Error fetching categories','moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        }

        if (!empty($categoriesList)) {
            foreach ($categoriesList as $category) {
                if (strcmp((string)$category['name'], $this->name) === 0) {
                    return (int)$category['productCategoryId'];
                }
            }
        }

        return 0;
    }

    /**
     * Create category
     *
     * @throws HelperException
     */
    private function createCategory(): int
    {
        $variables = [
            'data' => [
                'name' => $this->name,
                'parentId' =>  $this->parent_id === 0 ? null : (int)$this->parent_id
            ]
        ];

        try {
            $mutation = Categories::mutationProductCategoryCreate($variables);

            $category = $mutation['data']['productCategoryCreate']['data'] ?? [];
        } catch (APIExeption $e) {
            throw new HelperException(
                sprintf(__('Error creating category %s','moloni_es') ,$this->name),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        }

        if (isset($category['productCategoryId'])) {
            return (int)$category['productCategoryId'];
        }

        throw new HelperException(
            sprintf(__('Error creating category %s','moloni_es') ,$this->name),
            ['mutation' => $mutation]
        );
    }
}
