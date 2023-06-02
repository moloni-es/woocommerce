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
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\DocumentError;

class Payment
{
    public $payment_method_id;
    public $name;
    public $value = 0;

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
     * Loads a payment method by name
     *
     * @throws DocumentError
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

        try {
            $paymentMethods = PaymentMethods::queryPaymentMethods($variables);
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error fetching payment methods', 'moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData(),
                ]
            );
        }

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
     *
     * @throws DocumentError
     */
    public function create(): Payment
    {
        try {
            $mutation = (PaymentMethods::mutationPaymentMethodCreate($this->mapPropsToValues()))['data']['paymentMethodCreate']['data'] ?? [];
        } catch (APIExeption $e) {
            throw new DocumentError(
                sprintf(__('Error creating payment method (%s)', 'moloni_es'), $this->name),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData(),
                ]
            );
        }

        if (isset($mutation['paymentMethodId'])) {
            $this->payment_method_id = $mutation['paymentMethodId'];
            return $this;
        }

        throw new DocumentError(
            sprintf(__('Error creating payment method (%s)', 'moloni_es'), $this->name),
            [
                'mutation' => $mutation
            ]
        );
    }

    /**
     * Map this object properties to an array to insert/update a moloni Payment Value
     *
     * @return array
     */
    private function mapPropsToValues(): array
    {
        return [
            'data' => [
                'name' => $this->name
            ]
        ];
    }
}
