<?php
/**
 *
 *   Plugin Name:  Moloni
 *   Plugin URI:   https://woocommerce.moloni.es
 *   Description:  Send your orders automatically to your Moloni invoice software
 *   Version:      0.0.1
 *   Author:       moloni.es
 *   Author URI:   https://moloni.es
 *   License:      GPL2
 *   License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 *
 */

namespace MoloniES\Controllers;

use MoloniES\API\PaymentMethods;
use MoloniES\Error;

class Payment
{
    public $payment_method_id;
    public $name;
    public $value = 0;

    /**
     * Payment constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = trim($name);
    }

    /**
     * Loads a payment method by name
     * @throws Error
     */
    public function loadByName()
    {
        $variables = [
            'options' => [
                'search' => [
                    'field' => 'name',
                    'value' => $this->name
                ]
            ]
        ];

        $paymentMethods = PaymentMethods::queryPaymentMethods($variables);
        if (!empty($paymentMethods)) {
            foreach ($paymentMethods as $paymentMethod) {
                if ($paymentMethod['name'] === $this->name) {
                    $this->payment_method_id = (int) $paymentMethod['paymentMethodId'];
                    return $this;
                }
            }
        }

        return false;
    }

    /**
     * Create a Payment Methods based on the name
     * @throws Error
     */
    public function create()
    {
        $insert = (PaymentMethods::mutationPaymentMethodCreate($this->mapPropsToValues()))['data']['paymentMethodCreate']['data'];

        if (isset($insert['paymentMethodId'])) {
            $this->payment_method_id = $insert['paymentMethodId'];
            return $this;
        }

        throw new Error(__('Error creating payment method','moloni_es') . $this->name);
    }

    /**
     * Map this object properties to an array to insert/update a moloni Payment Value
     * @return array
     */
    private function mapPropsToValues()
    {
        return [
            'data' => [
                'name' => $this->name
            ]
        ];
    }
}