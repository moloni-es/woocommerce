<?php

namespace MoloniES\Services\MoloniProduct\Helpers\Variants;

use MoloniES\API\PropertyGroups;
use MoloniES\Enums\Boolean;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Services\MoloniProduct\Helpers\Abstracts\VariantHelperAbstract;
use WC_Product;
use WC_Product_Attribute;
use WC_Product_Variable;
use WP_Term;

class GetOrUpdatePropertyGroup extends VariantHelperAbstract
{
    /**
     * @var array
     */
    private $productAttributes;

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
        $this->productAttributes = $this->prepareProductAttributes($wcProduct);
        $this->propertyGroupId = $propertyGroupId;
    }

    public function handle(): array
    {
        if (empty($this->productAttributes)) {
            return [];
        }

        $queryParams = [
            'propertyGroupId' => $this->propertyGroupId
        ];

        try {
            $moloniPropertyGroup = PropertyGroups::queryPropertyGroup($queryParams)['data']['propertyGroup']['data'] ?? [];
        } catch (APIExeption $e) {
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

        /** Delete unwanted props */
        foreach ($propertyGroupForUpdate['properties'] as $idx => $group) {
            unset($propertyGroupForUpdate['properties'][$idx]['deletable']);

            foreach ($group['values'] as $idx2 => $property) {
                unset($propertyGroupForUpdate['properties'][$idx]['values'][$idx2]['deletable']);
            }
        }

        $updateNeeded = false;

        foreach ($this->productAttributes as $attributes) {
            foreach ($attributes as $attributeName => $options) {
                foreach ($options as $option) {
                    $propExistsKey = $this->findInName($propertyGroupForUpdate['properties'], $attributeName);

                    /** Property name exists */
                    if ($propExistsKey !== false) {
                        $propExists = $propertyGroupForUpdate['properties'][$propExistsKey];

                        $valueExistsKey = $this->findInCode($propExists['values'], $option);

                        /** Property value doesn't, add value */
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
                                ],
                            ],
                        ];
                    }
                }
            }
        }

        /** There was stuff missing, we need to update the property group */
        if ($updateNeeded) {
            $mutation = PropertyGroups::mutationPropertyGroupUpdate(['data' => $propertyGroupForUpdate]);

            $updatedPropertyGroup = $mutation['data']['propertyGroupUpdate']['data'] ?? [];

            if (empty($updatedPropertyGroup)) {
                // todo: throw error
                /* throw new MoloniProductException('Failed to update existing property group "{0}"', ['{0}' => $bestPropertyGroup['name'] ?? ''], ['mutation' => $mutation, 'props' => $propertyGroupForUpdate]);*/
            }

            return (new PrepareVariantPropertiesReturn($updatedPropertyGroup, $this->productAttributes))->handle();
        }

        /** This was a 100% match, we can return right away */
        return (new PrepareVariantPropertiesReturn($moloniPropertyGroup, $this->productAttributes))->handle();
    }

    /**
     * Prepare initial data structure for looping
     *
     * @param WC_Product|WC_Product_Variable $wcProduct
     *
     * @return array
     */
    private function prepareProductAttributes($wcProduct): array
    {
        $tempParsedAttributes = [];

        /**
         * [
         *      'wc_product_id => [
         *          'attribute_name' => [
         *              'option_a',
         *              'option_b',
         *              ...
         *          ]
         *      ]
         * ]
         */
        $result = [];

        /** @var WC_Product_Attribute[] $attributes */
        $attributes = $wcProduct->get_attributes();

        foreach ($attributes as $attributeTaxonomy => $productAttribute) {
            $attributeObject = wc_get_attribute($productAttribute->get_id());

            if (empty($attributeObject)) {
                // todo: throw error
            }

            $attributeName = $attributeObject->name;

            if (!isset($tempParsedAttributes[$attributeTaxonomy])) {
                $tempParsedAttributes[$attributeTaxonomy] = [
                    'name' => $attributeName,
                    'options' => [],
                ];
            }

            $tempParsedAttributes[$attributeTaxonomy]['options'] = wc_get_product_terms($wcProduct->get_id(), $attributeTaxonomy);
        }

        $ids = $wcProduct->get_children();

        foreach ($ids as $id) {
            $variationAttributes = wc_get_product($id)->get_attributes();

            $result[$id] = [];

            foreach ($variationAttributes as $taxonomy => $option) {
                if (empty($option)) {
                    continue;
                }

                $attributeName = $tempParsedAttributes[$taxonomy]['name'];

                $result[$id][$attributeName] = [];

                /** @var WP_Term $wpTerm */
                foreach ($tempParsedAttributes[$taxonomy]['options'] as $wpTerm) {
                    if ($wpTerm->slug === $option) {
                        $result[$id][$attributeName][] = $wpTerm->name;

                        break;
                    }
                }
            }
        }

        return $result;
    }
}
