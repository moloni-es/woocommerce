<?php

namespace MoloniES\Services\MoloniProduct\Helpers\Variants;

use MoloniES\API\PropertyGroups;
use MoloniES\Enums\Boolean;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\HelperException;
use MoloniES\Services\MoloniProduct\Helpers\Abstracts\VariantHelperAbstract;
use WC_Product;
use WC_Product_Variable;

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
     *
     * @throws HelperException
     */
    public function __construct($wcProduct, string $propertyGroupId)
    {
        $this->productAttributes = (new ParseProductProperties($wcProduct))->handle();
        $this->propertyGroupId = $propertyGroupId;
    }

    /**
     * Handler
     *
     * @throws HelperException
     */
    public function handle(): array
    {
        if (empty($this->productAttributes)) {
            return [];
        }

        $queryParams = [
            'propertyGroupId' => $this->propertyGroupId
        ];

        try {
            $query = PropertyGroups::queryPropertyGroup($queryParams);

            $moloniPropertyGroup = $query['data']['propertyGroup']['data'] ?? [];
        } catch (APIExeption $e) {
            throw new HelperException(__('Error fetching property group', 'moloni_es'));
        }

        /** Propery group is not found, exit process immediately */
        if (empty($moloniPropertyGroup)) {
            throw new HelperException(__('Error fetching property group', 'moloni_es'), ['query' => $query]);
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
            try {
                $mutation = PropertyGroups::mutationPropertyGroupUpdate(['data' => $propertyGroupForUpdate]);
            } catch (APIExeption $e) {
                throw new HelperException(
                    sprintf(__('Failed to update existing property group "%s"', 'moloni_es'), $bestPropertyGroup['name'] ?? ''),
                    ['message' => $e->getMessage(), 'data' => $e->getData()]
                );
            }

            $updatedPropertyGroup = $mutation['data']['propertyGroupUpdate']['data'] ?? [];

            if (empty($updatedPropertyGroup)) {
                throw new HelperException(
                    sprintf(__('Failed to update existing property group "%s"', 'moloni_es'), $bestPropertyGroup['name'] ?? ''),
                    ['mutation' => $mutation, 'props' => $propertyGroupForUpdate]
                );
            }

            return (new PrepareVariantPropertiesReturn($updatedPropertyGroup, $this->productAttributes))->handle();
        }

        /** This was a 100% match, we can return right away */
        return (new PrepareVariantPropertiesReturn($moloniPropertyGroup, $this->productAttributes))->handle();
    }
}
