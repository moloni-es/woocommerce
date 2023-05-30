<?php

namespace MoloniES\Services\MoloniProduct\Helpers\Variants;

use MoloniES\API\PropertyGroups;
use MoloniES\Enums\Boolean;
use MoloniES\Services\MoloniProduct\Helpers\Abstracts\VariantHelperAbstract;

class CreateEntirePropertyGroup extends VariantHelperAbstract
{
    private $moloniPropertyGroups;
    private $productAttributes;

    public function __construct(array $moloniPropertyGroups, array $productAttributes)
    {
        $this->moloniPropertyGroups = $moloniPropertyGroups;
        $this->productAttributes = $productAttributes;
    }

    public function handle(): array
    {
        $propsForInsert = [];

        /** Iterate over the shopify variants and prepare an array for insert into moloni */
        foreach ($this->productAttributes as $attributes) {
            foreach ($attributes as $attributeName => $options) {
                foreach ($options as $option) {
                    $nameExistsKey = $this->findInName($propsForInsert, $attributeName);

                    $newValue = [
                        'code' => $this->cleanReferenceString($option),
                        'value' => $option,
                        'ordering' => $nameExistsKey ? count($propsForInsert[$nameExistsKey]['values']) + 1 : 1,
                        'visible' => Boolean::YES,
                    ];

                    if ($nameExistsKey !== false) {
                        if (!$this->findInCode($propsForInsert[$nameExistsKey]['values'], $newValue['code'])) {
                            $propsForInsert[$nameExistsKey]['values'][] = $newValue;
                        }
                    } else {
                        $propsForInsert[] = [
                            'name' => $attributeName,
                            'ordering' => count($propsForInsert) + 1,
                            'values' => [
                                $newValue
                            ],
                            'visible' => Boolean::YES,
                        ];
                    }
                }
            }
        }

        /** Loop like crazy trying to find a free group name */
        for ($idx = 1; $idx <= 1000; $idx++) {
            $newGroupName = "Prestashop-" . str_pad($idx, 3, '0', STR_PAD_LEFT);

            if ($this->findInName($this->moloniPropertyGroups, $newGroupName) === false) {
                break;
            }
        }

        $creationVariables = [
            'data' => [
                'name' => $newGroupName,
                'properties' => $propsForInsert,
                'visible' => Boolean::YES,
            ]
        ];

        $mutation = PropertyGroups::mutationPropertyGroupCreate($creationVariables);

        $mutationData = $mutation['data']['propertyGroupCreate']['data'] ?? [];

        if (empty($mutationData)) {
            // todo: throw error
            /* throw new MoloniProductException(
                'Error creating {0} attribute group',
                ['{0}' => $newGroupName],
                ['mutation' => $mutation]
            );*/
        }

        return (new PrepareVariantPropertiesReturn($mutationData, $this->productAttributes))->handle();
    }
}