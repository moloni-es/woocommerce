<?php

namespace MoloniES\Services\MoloniProduct\Helpers\Variants;

use MoloniES\API\PropertyGroups;
use MoloniES\Enums\Boolean;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\HelperException;
use MoloniES\Services\MoloniProduct\Helpers\Abstracts\VariantHelperAbstract;
use WC_Product;
use WC_Product_Variable;

class FindOrCreatePropertyGroup extends VariantHelperAbstract
{
    /**
     * @var array
     */
    private $productAttributes;

    /**
     * Constructor
     *
     * @param WC_Product|WC_Product_Variable $wcProduct
     *
     * @throws HelperException
     */
    public function __construct($wcProduct)
    {
        $this->productAttributes = (new ParseProductProperties($wcProduct))->handle();
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

        try {
            $moloniPropertyGroups = PropertyGroups::queryPropertyGroups();
        } catch (APIExeption $e) {
            throw new HelperException(__('Error fetching property groups', 'moloni_es'));
        }

        $matches = [];

        /** Try to find the best property group */
        foreach ($moloniPropertyGroups as $moloniPropertyGroup) {
            if (empty($moloniPropertyGroup['propertyGroupId']) || empty($moloniPropertyGroup['properties'])) {
                continue;
            }

            $propertyGroupPropertiesMatchCount = 0;

            foreach ($this->productAttributes as $attributes) {
                foreach ($attributes as $attributeName => $options) {
                    foreach ($moloniPropertyGroup['properties'] as $property) {
                        if (strtolower($attributeName) === strtolower($property['name'])) {
                            $propertyGroupPropertiesMatchCount++;
                        }
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
            return (new CreateEntirePropertyGroup($moloniPropertyGroups, $this->productAttributes))->handle();
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
                                ]
                            ]
                        ];
                    }
                }
            }
        }

        unset($attributeName, $options, $option);

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

            $updatedGroup = $mutation['data']['propertyGroupUpdate']['data'] ?? [];

            if (empty($updatedGroup)) {
                throw new HelperException(
                    sprintf(__('Failed to update existing property group "%s"', 'moloni_es'), $bestPropertyGroup['name'] ?? ''),
                    ['mutation' => $mutation, 'props' => $propertyGroupForUpdate]
                );
            }

            return (new PrepareVariantPropertiesReturn($updatedGroup, $this->productAttributes))->handle();
        }

        /** This was a 100% match, we can return right away */
        return (new PrepareVariantPropertiesReturn($bestPropertyGroup, $this->productAttributes))->handle();
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
