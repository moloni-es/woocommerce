<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Error;

class Categories
{
    /**
     * Create an category
     *
     * @param array $variables variables of the query
     *
     * @return array returns some data of the created category
     * @throws Error
     */
    public static function mutationProductCategoryCreate($variables = [])
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

        return Curl::simple('categories/productCategoryCreate', $query, $variables, false);
    }

    /**
     * Gets all categories
     *
     * @param array $variables variables of the query
     *
     * @return array returns data of the categories
     * @throws Error
     */
    public static function queryProductCategories($variables = [])
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

        return Curl::complex('categories/productCategories', $query, $variables, 'productCategories', false);
    }

    /**
     * Get the category of an product
     *
     * @param array $variables variables of the query
     *
     * @return array returns category data
     * @throws Error
     */
    public static function queryProductCategory($variables = [])
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

        return Curl::simple('categories/productCategory', $query, $variables, false);
    }
}
