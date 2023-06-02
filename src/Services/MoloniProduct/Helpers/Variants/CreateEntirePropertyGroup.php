<?php

namespace MoloniES\Services\MoloniProduct\Helpers\Variants;

use MoloniES\API\PropertyGroups;
use MoloniES\Enums\Boolean;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\HelperException;
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

    /**
     * Handler
     *
     * @throws HelperException
     */
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

        try {
            $mutation = PropertyGroups::mutationPropertyGroupCreate($creationVariables);
        } catch (APIExeption $e) {
            throw new HelperException(
                sprintf(__('Error creating %s attribute group', 'moloni_es'), $newGroupName),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        }

        $mutationData = $mutation['data']['propertyGroupCreate']['data'] ?? [];

        if (empty($mutationData)) {
            throw new HelperException(
                sprintf(__('Error creating %s attribute group', 'moloni_es'), $newGroupName),
                ['mutation' => $mutation]
            );
        }

        return (new PrepareVariantPropertiesReturn($mutationData, $this->productAttributes))->handle();
    }
}
