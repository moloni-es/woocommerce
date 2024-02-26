<?php

namespace MoloniES\Controllers;

use MoloniES\API\DeliveryMethods;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\DocumentError;

class DeliveryMethod
{
    public $delivery_method_id = 0;
    public $name;

    /**
     * Payment constructor.
     *
     * @param string|null $name
     */
    public function __construct(?string $name = '')
    {
        $this->name = trim($name);
    }

    /**
     * Load delivery method by name
     *
     * @return bool
     *
     * @throws DocumentError
     */
    public function loadByName(): bool
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
        try {
            $query = DeliveryMethods::queryDeliveryMethods($variables);
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error fetching delivery methods', 'moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData(),
                ]
            );
        }

        if (!empty($query) && isset($query[0]['deliveryMethodId'])) {
            $this->delivery_method_id = $query[0]['deliveryMethodId'];

            return true;
        }

        return false;
    }

    /**
     * Load company default delivery method
     *
     * @return bool
     *
     * @throws DocumentError
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
        try {
            $query = DeliveryMethods::queryDeliveryMethods($variables);
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error fetching delivery methods', 'moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData(),
                ]
            );
        }

        if (!empty($query) && isset($query[0]['deliveryMethodId'])) {
            $this->delivery_method_id = $query[0]['deliveryMethodId'];

            return true;
        }

        return false;
    }

    /**
     * Create a Payment Methods based on the name
     *
     * @throws DocumentError
     */
    public function create(): DeliveryMethod
    {
        $variables = [
            'data' => [
                'name' => $this->name,
                'isDefault' => false
            ]
        ];
        try {
            $mutation = DeliveryMethods::mutationDeliveryMethodCreate($variables);
        } catch (APIExeption $e) {
            throw new DocumentError(
                sprintf(__('Error creating delivery method (%s)', 'moloni_es'), $this->name),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData(),
                ]
            );
        }

        if (!empty($mutation) &&
            isset($mutation['data']['deliveryMethodCreate']['data']['deliveryMethodId'])) {
            $this->delivery_method_id = (int)$mutation['data']['deliveryMethodCreate']['data']['deliveryMethodId'];

            return $this;
        }

        throw new DocumentError(
            sprintf(__('Error creating delivery method (%s)', 'moloni_es'), $this->name),
            [
                'mutation' => $mutation
            ]
        );
    }
}
