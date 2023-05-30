<?php

namespace MoloniES\Services\MoloniProduct\Helpers\Variants;

use MoloniES\Services\MoloniProduct\Helpers\Abstracts\VariantHelperAbstract;

class PrepareVariantPropertiesReturn extends VariantHelperAbstract
{

    private $moloniPropertyGroup;
    private $productAttributes;

    public function __construct(array $moloniPropertyGroup, array $productAttributes)
    {
        $this->moloniPropertyGroup = $moloniPropertyGroup;
        $this->productAttributes = $productAttributes;
    }

    public function handle(): array
    {
        $result = [];

        foreach ($this->productAttributes as $wcProductId => $attributes) {
            $variantProperties = [];

            foreach ($attributes as $attributesName => $options) {
                foreach ($options as $option) {
                    $propExistsKey = $this->findInName($this->moloniPropertyGroup['properties'], $attributesName);

                    if ($propExistsKey === false) {
                        // todo: throw error
                        //throw new MoloniProductException('Failed to find matching property name for "{0}".', ['{0}' => $attributesName]);
                    }

                    $propExists = $this->moloniPropertyGroup['properties'][$propExistsKey];

                    $valueExists = $this->findInCode($propExists['values'], $option);

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

            $result[$wcProductId] = $variantProperties;
        }

        return [
            'propertyGroupId' => $this->moloniPropertyGroup['propertyGroupId'],
            'variations' => $result,
        ];
    }
}