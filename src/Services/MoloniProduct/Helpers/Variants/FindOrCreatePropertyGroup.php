<?php

namespace MoloniES\Services\MoloniProduct\Helpers\Variants;

use MoloniES\API\PropertyGroups;
use MoloniES\Enums\Boolean;
use MoloniES\Exceptions\Error;
use MoloniES\Services\MoloniProduct\Helpers\Abstracts\VariantHelperAbstract;
use WC_Product;
use WC_Product_Attribute;
use WC_Product_Variable;
use WP_Term;

class FindOrCreatePropertyGroup extends VariantHelperAbstract
{
    /**
     * @var array
     */
    private $attributes;

    /**
     * Constructor
     *
     * @param WC_Product|WC_Product_Variable $wcProduct
     */
    public function __construct($wcProduct)
    {
        $this->attributes = $this->prepareProductAttributes($wcProduct);
    }

    public function handle(): array
    {
        if (empty($this->attributes)) {
            return [];
        }

        try {
            $moloniPropertyGroups = PropertyGroups::queryPropertyGroups();
        } catch (Error $e) {
            // todo: thorw error
            die;
        }

        $matches = [];

        /** Try to find the best property group */
        foreach ($moloniPropertyGroups as $moloniPropertyGroup) {
            if (empty($moloniPropertyGroup['propertyGroupId']) || empty($moloniPropertyGroup['properties'])) {
                continue;
            }

            $propertyGroupPropertiesMatchCount = 0;

            foreach ($this->attributes as $attributeName => $options) {
                foreach ($moloniPropertyGroup['properties'] as $property) {
                    if (strtolower($attributeName) === strtolower($property['name'])) {
                        $propertyGroupPropertiesMatchCount++;
                    }
                }
            }

            $matches[] = [
                'propertyGroupId' => $moloniPropertyGroup['propertyGroupId'],
                'count' => $propertyGroupPropertiesMatchCount,
            ];
        }

        unset($attributeName, $options, $moloniPropertyGroup);

        // Sort by best match descending
        $this->orderMatches($matches);

        /**
         * No matches, or the best match is 0
         * We need to fully create it
         */
        if (empty($matches) || $matches[0]['count'] === 0) {
            return (new CreateEntirePropertyGroup($moloniPropertyGroups, $this->attributes))->handle();
        }

        /**
         * A match was found
         * If it was partial, we need to do a propertyGroup update to add the missing stuff
         * If it was 100% match, we can just return
         */
        $bestPropertyGroupId = (int)$matches[0]['propertyGroupId'];
        $bestPropertyGroup = $this->findInPropertyGroup($moloniPropertyGroups, $bestPropertyGroupId);

        $propertyGroupForUpdate = [
            'propertyGroupId' => $bestPropertyGroup['propertyGroupId'],
            'properties' => $bestPropertyGroup['properties'],
        ];

        /** Delete unwanted props */
        foreach ($propertyGroupForUpdate['properties'] as $idx => $group) {
            unset($propertyGroupForUpdate['properties'][$idx]['deletable']);

            foreach ($group['values'] as $idx2 => $property) {
                unset($propertyGroupForUpdate['properties'][$idx]['values'][$idx2]['deletable']);
            }
        }

        $updateNeeded = false;

        foreach ($this->attributes as $attributeName => $options) {
            foreach ($options as $option) {
                $propExistsKey = $this->findInName($propertyGroupForUpdate['properties'], $attributeName);

                /** Property name exists */
                if ($propExistsKey !== false) {
                    $propExists = $propertyGroupForUpdate['properties'][$propExistsKey];

                    $valueExistsKey = $this->findInCode($propExists['values'], $option);

                    // Property value doesn't, add value
                    if ($valueExistsKey === false) {
                        $updateNeeded = true;

                        $nextOrdering = $this->getNextPropertyOrder($propExists['values']);

                        $propertyGroupForUpdate['properties'][$propExistsKey]['values'][] = [
                            'code' => $this->cleanReferenceString($option),
                            'value' => $option,
                            'ordering' => $nextOrdering,
                            'visible' => Boolean::YES,
                        ];
                    }
                } else {
                    /**
                     * Property name doesn't exist
                     * Need to create property and the value
                     */

                    $updateNeeded = true;

                    $nextOrdering = $this->getNextPropertyOrder($propertyGroupForUpdate['properties']);

                    $propertyGroupForUpdate['properties'][] = [
                        'ordering' => $nextOrdering,
                        'name' => $attributeName,
                        'visible' => Boolean::YES,
                        'values' => [
                            [
                                'code' => $this->cleanReferenceString($option),
                                'value' => $option,
                                'visible' => Boolean::YES,
                                'ordering' => 1,
                            ]
                        ]
                    ];
                }
            }
        }

        unset($attributeName, $options, $option);

        /** There was stuff missing, we need to update the property group */
        if ($updateNeeded) {
            $mutation = PropertyGroups::mutationPropertyGroupUpdate(
                ['data' => $propertyGroupForUpdate]
            );

            $updatedGroup = $mutation['data']['propertyGroupUpdate']['data'] ?? [];

            if (empty($updatedGroup)) {
                // todo: throw error

                /*throw new MoloniProductException('Failed to update existing property group "{0}"', [
                    '{0}' => $bestPropertyGroup['name'] ?? ''
                ], ['mutation' => $mutation, 'props' => $propertyGroupForUpdate]);*/
            }

            return $updatedGroup;
        }

        /** This was a 100% match, we can return right away */
        return $bestPropertyGroup;
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

    //          Privates          //

    /**
     * Orders matches in descending order
     *
     * @param array $matches
     *
     * @return void
     */
    private function orderMatches(array &$matches): void
    {
        $countColumn = array_column($matches, 'count');

        array_multisort($countColumn, SORT_DESC, $matches);
    }
}