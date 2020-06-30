<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Error;

class PaymentMethods
{
    /**
     * Get payment methods info
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryPaymentMethod($variables)
    {
        $query = 'query paymentMethod($companyId: Int!,$paymentMethodId: Int!)
        {
            paymentMethod(companyId: $companyId,paymentMethodId: $paymentMethodId) 
            {
                errors
                {
                    field
                    msg
                }
                data
                {
                    paymentMethodId
                    name
                    type
                    commission
                }
            }
        }';

        return Curl::simple('paymentmethods/paymentMethod', $query, $variables, false);
    }

    /**
     * Get All Payment Methods from Moloni ES
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryPaymentMethods($variables)
    {
        $query = 'query paymentMethods($companyId: Int!,$options: PaymentMethodOptions)
        {
            paymentMethods(companyId: $companyId, options: $options) 
            {
                errors
                {
                    field
                    msg
                }
                options
                {
                    pagination
                    {
                        page
                        qty
                        count
                    }
                }
                data
                {
                    paymentMethodId
                    name
                    type
                    commission
                }
            }
        }';

        return Curl::complex('paymentmethods/paymentMethods', $query, $variables, 'paymentMethods', false);
    }

    /**
     * Creates a payment method
     * @param $variables
     * @return mixed
     * @throws Error
     */
    public static function mutationPaymentMethodCreate($variables)
    {
        $query = 'mutation paymentMethodCreate($companyId: Int!,$data: PaymentMethodInsert!)
        {
            paymentMethodCreate(companyId: $companyId,data: $data)
            {
                data
                {
                    paymentMethodId
                    name
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('paymentmethods/paymentMethodCreate', $query, $variables, false);
    }
}
