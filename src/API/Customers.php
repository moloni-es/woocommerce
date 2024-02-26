<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Customers extends EndpointAbstract
{
    /**
     * Creates a costumer
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function mutationCustomerCreate(?array $variables = []): array
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
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationCustomerUpdate(?array $variables = [])
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
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryCustomer(?array $variables = []): array
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
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryCustomers(?array $variables = []): array
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

    /**
     * Gets the next number available for customers
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryCustomerNextNumber(?array $variables = []): array
    {
        $query = 'query customerNextNumber($companyId: Int!, $options: GetNextCustomerNumberOptions)
        {
            customerNextNumber(companyId: $companyId, options: $options)
            {
                data
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('customers/customerNextNumber', $query, $variables);
    }
}
