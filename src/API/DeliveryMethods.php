<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Error;

class DeliveryMethods
{
    /**
     * Create a new delivery methods
     *
     * @param array $variables Request variables
     *
     * @return mixed
     *
     * @throws Error
     */
    public static function mutationDeliveryMethodCreate($variables = []) {
        $query = 'mutation deliveryMethodCreate($companyId: Int!,$data: DeliveryMethodInsert!)
        {
            deliveryMethodCreate(companyId: $companyId,data: $data) 
            {
                errors
                {
                    field
                    msg
                }
                data
                {
                    deliveryMethodId
                    name
                }
            }
        }';

        return Curl::simple('deliverymethods/deliveryMethodCreate', $query, $variables);
    }

    /**
     * Get All DeliveryMethods from Moloni ES
     *
     * @param array $variables Request variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryDeliveryMethods($variables = [])
    {
        $query = 'query deliveryMethods($companyId: Int!,$options: DeliveryMethodOptions)
        {
            deliveryMethods(companyId: $companyId,options: $options) 
            {
                errors
                {
                    field
                    msg
                }
                data
                {
                    deliveryMethodId
                    name
                    isDefault
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
            }
        }';

        return Curl::complex('deliverymethods/deliveryMethods', $query, $variables, 'deliveryMethods');
    }
}