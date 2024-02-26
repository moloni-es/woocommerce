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
        $query = 'mutation productCategoryCreate($companyId: Int!,$data: ProductCategoryInsert!)
        {
            productCategoryCreate(companyId: $companyId,data: $data)
            {
                data
                {
                    productCategoryId
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('categories/productCategoryCreate', $query, $variables);
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
        $query = 'query productCategories($companyId: Int!,$options: ProductCategoryOptions)
        {
            productCategories(companyId: $companyId,options: $options)
            {
                data
                {
                    productCategoryId
                    name
                    parent
                    {
                        productCategoryId
                        name
                    }
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
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::complex('categories/productCategories', $query, $variables, 'productCategories');
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
        $query = 'query productCategory($companyId: Int!,$productCategoryId: Int!)
        {
            productCategory(companyId: $companyId,productCategoryId: $productCategoryId)
            {
                data
                {
                    name
                    posVisible
                    summary
                    visible
                    parent
                    {
                        productCategoryId
                        name
                    }
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('categories/productCategory', $query, $variables);
    }
}
