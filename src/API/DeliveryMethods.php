<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Error;

class DeliveryMethods
{
    /**
     * Get All DeliveryMethods from Moloni ES
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryDeliveryMethods($variables)
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

        return Curl::complex('deliverymethods/deliveryMethods', $query, $variables, 'deliveryMethods', false);
    }
}