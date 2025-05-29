<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Products extends EndpointAbstract
{
    /**
     * Create a new product
     *
     * @param array|null $variables
     *
     * @return array returns some data of the created product
     *
     * @throws APIExeption
     */
    public static function mutationProductCreate(?array $variables = []): ?array
    {
        $query = self::loadMutation('productCreate');

        return Curl::simple('productCreate', $query, $variables);
    }

    /**
     * Update a product
     *
     * @param array|null $variables
     *
     * @return array returns some data of the updated product
     *
     * @throws APIExeption
     */
    public static function mutationProductUpdate(?array $variables = []): ?array
    {
        $query = self::loadMutation('productUpdate');

        return Curl::simple('productUpdate', $query, $variables);
    }

    /**
     * Gets the information of a product
     *
     * @param array|null $variables
     *
     * @return array information of the product
     *
     * @throws APIExeption
     */
    public static function queryProduct(?array $variables = []): ?array
    {
        $query = self::loadQuery('product');

        return Curl::simple('product', $query, $variables);
    }

    /**
     * Gets all products
     *
     * @param array|null $variables
     *
     * @return array
     * @throws APIExeption
     */
    public static function queryProducts(array $variables = []): ?array
    {
        $query = self::loadQuery('products');

        return Curl::simple('products', $query, $variables);
    }
}
