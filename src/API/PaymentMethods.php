<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class PaymentMethods extends EndpointAbstract
{
    /**
     * Get payment methods info
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws APIExeption
     */
    public static function queryPaymentMethod(?array $variables = []): array
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

        return Curl::simple('paymentmethods/paymentMethod', $query, $variables);
    }

    /**
     * Get All Payment Methods from Moloni ES
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryPaymentMethods(?array $variables = []): array
    {
        $action = 'paymentmethods/paymentMethods';

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

        if (empty(self::$cache[$action])) {
            self::$cache[$action] = Curl::complex($action, $query, $variables, 'paymentMethods');
        }

        return self::$cache[$action];
    }

    /**
     * Creates a payment method
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationPaymentMethodCreate(?array $variables = [])
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

        return Curl::simple('paymentmethods/paymentMethodCreate', $query, $variables);
    }
}
