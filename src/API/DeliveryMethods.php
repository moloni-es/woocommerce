<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class DeliveryMethods extends EndpointAbstract
{
    /**
     * Create a new delivery methods
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationDeliveryMethodCreate(?array $variables = []) {
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
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws APIExeption
     */
    public static function queryDeliveryMethods(?array $variables = []): array
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
