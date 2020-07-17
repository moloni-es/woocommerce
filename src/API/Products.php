<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Error;

class Products
{
    /**
     * Create a new product
     *
     * @param array $variables variables of the query
     *
     * @return array returns some data of the created product
     * @throws Error
     */
    public static function mutationProductCreate($variables = [])
    {
        $query = 'mutation productCreate($companyId: Int!,$data: ProductInsert!)
        {
            productCreate(companyId: $companyId,data: $data) 
            {
                data{
                    productId
                    name
                }
                errors{
                    field
                    msg
                }
            }
        }';

        return Curl::simple('products/productCreate', $query, $variables, false);
    }

    /**
     * Update a product
     *
     * @param array $variables variables of the query
     *
     * @return array returns some data of the updated product
     * @throws Error
     */
    public static function mutationProductUpdate($variables = [])
    {
        $query = 'mutation productUpdate($companyId: Int!,$data: ProductUpdate!)
        {
            productUpdate(companyId: $companyId ,data: $data)
            {
                data
                {
                    productId
                    name
                    reference
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('products/productUpdate', $query, $variables, false);
    }

    /**
     * Gets the information of a product
     *
     * @param array $variables variables of the query
     *
     * @return array information of the product
     * @throws Error
     */
    public static function queryProduct($variables = [])
    {
        $query = 'query product($companyId: Int!,$productId: Int!)
        {
            product(companyId: $companyId,productId: $productId)
            {
                data
                {
                    visible
                    name
                    productId
                    type
                    reference
                    summary
                    price
                    priceWithTaxes
                    hasStock
                    stock
                    minStock
                    measurementUnit
                    {
                        measurementUnitId
                        name
                    }   
                    warehouse
                    {
                        warehouseId
                    }
                    productCategory
                    {
                        productCategoryId
                        name
                    }                
                    variants
                    {
                        visible
                        productId
                        name
                        reference
                        summary
                        price
                        priceWithTaxes
                        hasStock
                        stock
                        propertyPairs
                        {
                            property
                            {
                                name
                            }
                            propertyValue
                            {
                                code
                                value
                            }
                        }
                    }
                    parent
                    {
                        productId
                        name
                    }
                    propertyGroup
                    {
                        propertyGroupId
                        name
                        properties
                        {
                            propertyId
                            name
                            ordering
                            values
                            {
                                propertyValueId
                                code
                                value
                            }
                        }
                    }
                    taxes
                    {
                        tax
                        {
                            taxId
                            value
                            name
                        }
                        value
                        ordering
                    }       
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('products/product', $query, $variables, false);
    }

    /**
     * Gets all products
     * @param array $variables
     * @return array|bool
     * @throws Error
     */
    public static function queryProducts($variables = [])
    {
        $query = 'query products($companyId: Int!,$options: ProductOptions)
        {
            products(companyId: $companyId,options: $options)
            {
                data
                {
                    visible
                    name
                    productId
                    type
                    reference
                    summary
                    price
                    priceWithTaxes
                    hasStock
                    stock
                    minStock
                    measurementUnit
                    {
                        measurementUnitId
                        name
                    }   
                    warehouse
                    {
                        warehouseId
                    }
                    productCategory
                    {
                        productCategoryId
                        name
                    }                
                    variants
                    {
                        productId
                        name
                        reference
                    }
                    parent
                    {
                        productId
                        name
                    }
                    propertyGroup
                    {
                        propertyGroupId
                        name
                        properties
                        {
                            propertyId
                            name
                            ordering
                            values
                            {
                                propertyValueId
                                code
                                value
                            }
                        }
                    }
                    taxes
                    {
                        tax
                        {
                            taxId
                            value
                            name
                        }
                        value
                        ordering
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

        return Curl::complex('products/products', $query, $variables, 'products', false);
    }
}
