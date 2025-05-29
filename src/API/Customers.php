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
        $query = self::loadMutation('customerCreate');

        return Curl::simple('customerCreate', $query, $variables);
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
        $query = self::loadMutation('customerUpdate');

        return Curl::simple('customerUpdate', $query, $variables);
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
        $query = self::loadQuery('customer');

        return Curl::simple('customer', $query, $variables);
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
        $query = self::loadQuery('customers');

        return Curl::simple('customers', $query, $variables);
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
        $query = self::loadQuery('customerNextNumber');

        return Curl::simple('customerNextNumber', $query, $variables);
    }
}
