<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Error;

class Customers
{
    /**
     * Creates an costumer
     *
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function mutationCustomerCreate($variables = [])
    {
        $query = 'mutation customerCreate($companyId: Int!,$data: CustomerInsert!)
        {
            customerCreate(companyId: $companyId,data: $data)
            {
                data
                {
                    customerId
                    name
                    vat
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('customers/customerCreate', $query, $variables);
    }

    /**
     * Updates an costumer
     * @param array $variables
     * @return mixed
     * @throws Error
     */
    public static function mutationCustomerUpdate($variables = [])
    {
        $query = 'mutation customerUpdate($companyId: Int!,$data: CustomerUpdate!)
        {
            customerUpdate(companyId: $companyId,data: $data)
            {
                data
                {
                    customerId
                    name
                    number
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('customers/customerUpdate', $query, $variables);
    }

    /**
     * Gets costumer information
     *
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryCustomer($variables = [])
    {
        $query = 'query customer($companyId: Int!,$customerId: Int!,$options: CustomerOptionsSingle)
        {
            customer(companyId: $companyId,customerId: $customerId,options: $options)
            {
                data
                {
                    customerId
                    name
                    discount
                    documentSet
                    {
                        documentSetId
                        name
                    }
                    vat
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('customers/customer', $query, $variables);
    }

    /**
     * Gets costumers of the company
     *
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryCustomers($variables = [])
    {
        $query = 'query customers($companyId: Int!,$options: CustomerOptions)
        {
            customers(companyId: $companyId,options: $options)
            {
                data
                {
                    customerId
                    name
                    number
                    discount
                    documentSet
                    {
                        documentSetId
                        name
                    }
                    country
                    {
                        countryId
                    }
                    language
                    {
                        languageId
                    }
                    vat
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('customers/customers', $query, $variables);
    }
}
