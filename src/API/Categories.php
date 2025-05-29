<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Categories extends EndpointAbstract
{
    /**
     * Create a category
     *
     * @param array|null $variables variables of the query
     *
     * @return array returns some data of the created category
     *
     * @throws APIExeption
     */
    public static function mutationProductCategoryCreate(?array $variables = []): array
    {
        $query = self::loadMutation('productCategoryCreate');

        return Curl::simple('productCategoryCreate', $query, $variables);
    }

    /**
     * Gets all categories
     *
     * @param array|null $variables
     *
     * @return array returns data of the categories
     *
     * @throws APIExeption
     */
    public static function queryProductCategories(?array $variables = []): array
    {
        $query = self::loadQuery('productCategories');

        return Curl::complex('productCategories', $query, $variables);
    }

    /**
     * Get the category of a product
     *
     * @param array|null $variables
     *
     * @return array returns category data
     *
     * @throws APIExeption
     */
    public static function queryProductCategory(?array $variables = []): array
    {
        $query = self::loadQuery('productCategory');

        return Curl::simple('productCategory', $query, $variables);
    }
}
