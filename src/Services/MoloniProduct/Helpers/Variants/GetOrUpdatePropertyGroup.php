<?php

namespace MoloniES\Services\MoloniProduct\Helpers\Variants;

use MoloniES\API\PropertyGroups;
use MoloniES\Enums\Boolean;
use MoloniES\Exceptions\Error;
use WC_Product;
use WC_Product_Attribute;
use WC_Product_Variable;
use WP_Term;

class GetOrUpdatePropertyGroup
{
    /**
     * @var array
     */
    private $attributes;

    /**
     * @var string
     */
    private $propertyGroupId;

    /**
     * Constructor
     *
     * @param WC_Product|WC_Product_Variable $wcProduct
     * @param string $propertyGroupId
     */
    public function __construct($wcProduct, string $propertyGroupId)
    {
        $this->attributes = $this->prepareProductAttributes($wcProduct);
        $this->propertyGroupId = $propertyGroupId;
    }

    public function handle(): array
    {
        if (empty($this->attributes)) {
            return [];
        }

        $queryParams = [
            'propertyGroupId' => $this->propertyGroupId
        ];

        try {
            $moloniPropertyGroup = PropertyGroups::queryPropertyGroup($queryParams)['data']['propertyGroup']['data'] ?? [];
        } catch (Error $e) {
            // todo: throw error
            die;
        }

        /** Propery group is not found, exit process immediately */
        if (empty($moloniPropertyGroup)) {
            // todo: throw error
            die;
            // throw new MoloniProductException('Error fetching property group', [], $queryParams);
        }

        $propertyGroupForUpdate = [
            'propertyGroupId' => $moloniPropertyGroup['propertyGroupId'],
            'properties' => $moloniPropertyGroup['properties'],
        ];

        // Delete unwanted props
        foreach ($propertyGroupForUpdate['properties'] as $idx => $group) {
            unset($propertyGroupForUpdate['properties'][$idx]['deletable']);

            foreach ($group['values'] as $idx2 => $property) {
                unset($propertyGroupForUpdate['properties'][$idx]['values'][$idx2]['deletable']);
            }
        }

        $updateNeeded = false;

        foreach ($this->attributes as $groups) {
            foreach ($groups as $groupName => $attributes) {
                foreach ($attributes as $attribute) {
                    $propExistsKey = $this->findInName($propertyGroupForUpdate['properties'], $groupName);

                    // Property name exists
                    if ($propExistsKey !== false) {
                        $propExists = $propertyGroupForUpdate['properties'][$propExistsKey];

                        $valueExistsKey = $this->findInCode(
                            $propExists['values'],
                            $attribute,
                            [$this, 'cleanReferenceString']
                        );

                        // Property value doesn't, add value
                        if ($valueExistsKey === false) {
                            $updateNeeded = true;

                            $nextOrdering = $this->getNextPropertyOrder($propExists['values']);

                            $propertyGroupForUpdate['properties'][$propExistsKey]['values'][] = [
                                'code' => $this->cleanReferenceString($attribute),
                                'value' => $attribute,
                                'ordering' => $nextOrdering,
                                'visible' => Boolean::YES,
                            ];
                        }

                        // Property name doesn't exist
                        // need to create property and the value
                    } else {
                        $updateNeeded = true;

                        $nextOrdering = $this->getNextPropertyOrder($propertyGroupForUpdate['properties']);

                        $propertyGroupForUpdate['properties'][] = [
                            'ordering' => $nextOrdering,
                            'name' => $groupName,
                            'visible' => Boolean::YES,
                            'values' => [
                                [
                                    'code' => $this->cleanReferenceString($attribute),
                                    'value' => $attribute,
                                    'visible' => Boolean::YES,
                                    'ordering' => 1,
                                ],
                            ],
                        ];
                    }
                }
            }
        }

        // There was stuff missing, we need to update the property group
        if ($updateNeeded) {
            $mutation = PropertyGroups::mutationPropertyGroupUpdate(['data' => $propertyGroupForUpdate]);

            $updatedPropertyGroup = $mutation['data']['propertyGroupUpdate']['data'] ?? [];

            if (empty($updatedPropertyGroup)) {
                // todo: throw error
                /* throw new MoloniProductException('Failed to update existing property group "{0}"', ['{0}' => $bestPropertyGroup['name'] ?? ''], ['mutation' => $mutation, 'props' => $propertyGroupForUpdate]);*/
            }

            return $updatedPropertyGroup;
        }

        // This was a 100% match, we can return right away
        return $moloniPropertyGroup;
    }

    /**
     * Prepare initial data structure for looping
     *
     * @param WC_Product|WC_Product_Variable $wpProduct
     *
     * @return array
     */
    private function prepareProductAttributes($wpProduct): array
    {
        /**
         * [
         *       'group_name' => [
         *           'attribute_a',
         *           'attribute_b',
         *           ...
         *       ]
         * ]
         */
        $result = [];

        /** @var WC_Product_Attribute[] $attributes */
        $attributes = $wpProduct->get_attributes();

        foreach ($attributes as $attributeTaxonomy => $productAttribute) {
            $attributeObject = wc_get_attribute($productAttribute->get_id());

            if (empty($attributeObject)) {
                // todo: throw error
            }

            $attributeName = $attributeObject->name;

            if (!isset($result[$attributeName])) {
                $result[$attributeName] = [];
            }

            /** @var WP_Term[] $wcTerms */
            $wcTerms = wc_get_product_terms($wpProduct->get_id(), $attributeTaxonomy);

            foreach ($wcTerms as $wcTerm) {
                $result[$attributeName][] = $wcTerm->name;
            }
        }

        return $result;
    }
}