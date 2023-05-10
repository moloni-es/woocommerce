<?php

namespace MoloniES\Controllers;

use MoloniES\API\DeliveryMethods;
use MoloniES\Exceptions\Error;

class DeliveryMethod
{
    public $delivery_method_id = 0;
    public $name;

    /**
     * Payment constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = trim($name);
    }

    /**
     * Load delivery method by name
     *
     * @return bool
     *
     * @throws Error
     */
    public function loadByName()
    {
        $variables = [
            'options' => [
                'filter' => [
                    'field' => 'name',
                    'comparison' => 'eq',
                    'value' => $this->name
                ]
            ]
        ];
        $deliveryMethods = DeliveryMethods::queryDeliveryMethods($variables);

        if (!empty($deliveryMethods) && isset($deliveryMethods[0]['deliveryMethodId'])) {
            $this->delivery_method_id = $deliveryMethods[0]['deliveryMethodId'];

            return true;
        }

        return false;
    }

    /**
     * Load company default delivery method
     *
     * @return bool
     *
     * @throws Error
     */
    public function loadDefault(): bool
    {
        $variables = [
            'options' => [
                'filter' => [
                    'field' => 'isDefault',
                    'comparison' => 'eq',
                    'value' => '1'
                ]
            ]
        ];
        $deliveryMethods = DeliveryMethods::queryDeliveryMethods($variables);

        if (!empty($deliveryMethods) && isset($deliveryMethods[0]['deliveryMethodId'])) {
            $this->delivery_method_id = $deliveryMethods[0]['deliveryMethodId'];

            return true;
        }

        return false;
    }

    /**
     * Create a Payment Methods based on the name
     *
     * @throws Error
     */
    public function create()
    {
        $variables = [
            'data' => [
                'name' => $this->name,
                'isDefault' => false
            ]
        ];
        $deliveryMethod = DeliveryMethods::mutationDeliveryMethodCreate($variables);

        if (!empty($deliveryMethod) &&
            isset($deliveryMethod['data']['deliveryMethodCreate']['data']['deliveryMethodId'])) {
            $this->delivery_method_id = (int)$deliveryMethod['data']['deliveryMethodCreate']['data']['deliveryMethodId'];

            return $this;
        }

        throw new Error(__('Error creating delivery method', 'moloni_es') . $this->name);
    }
}