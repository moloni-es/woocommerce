<?php

namespace MoloniES\Services\MoloniProduct\Helpers\Variants;

use MoloniES\Services\MoloniProduct\Helpers\Abstracts\VariantHelperAbstract;

class PrepareVariantPropertiesReturn extends VariantHelperAbstract
{

    private $moloniPropertyGroup;
    private $prestashopCombinations;

    public function __construct(array $moloniPropertyGroup, array $prestashopCombinations)
    {
        $this->moloniPropertyGroup = $moloniPropertyGroup;
        $this->prestashopCombinations = $prestashopCombinations;
    }

    public function handle(): array
    {
        $result = [];

        foreach ($this->prestashopCombinations as $combinationId => $groups) {
            $variantProperties = [];

            foreach ($groups as $groupName => $attributes) {
                foreach ($attributes as $attribute) {
                    $propExistsKey = $this->findInName($this->moloniPropertyGroup['properties'], $groupName);

                    if ($propExistsKey === false) {
                        // todo: throw error
                        //throw new MoloniProductException('Failed to find matching property name for "{0}".', ['{0}' => $groupName]);
                    }

                    $propExists = $this->moloniPropertyGroup['properties'][$propExistsKey];

                    $valueExists = $this->findInCode($propExists['values'], $attribute);

                    if ($valueExists === false) {
                        // todo: throw error
                        // throw new MoloniProductException('Failed to find matching property value for "{0}"', ['{0}' => $attribute]);
                    }

                    $variantProperties[] = [
                        'propertyId' => $propExists['propertyId'],
                        'propertyValueId' => $valueExists['propertyValueId'],
                    ];
                }
            }

            $result[$combinationId] = $variantProperties;
        }

        return [
            'propertyGroupId' => $this->moloniPropertyGroup['propertyGroupId'],
            'variants' => $result,
        ];
    }
}