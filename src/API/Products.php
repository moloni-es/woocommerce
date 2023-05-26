<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Exceptions\Error;

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
    public static function mutationProductCreate(array $variables = []): array
    {
        $query = 'mutation productCreate($companyId: Int!,$data: ProductInsert!)
        {
            productCreate(companyId: $companyId,data: $data) 
            {
                data
                {
                    ' . self::getProductSegment() . '
                    ' . self::getVariantSegment() . '
                }
                errors{
                    field
                    msg
                }
            }
        }';

        return Curl::simple('products/productCreate', $query, $variables);
    }

    /**
     * Update a product
     *
     * @param array $variables variables of the query
     *
     * @return array returns some data of the updated product
     * @throws Error
     */
    public static function mutationProductUpdate(array $variables = []): array
    {
        $query = 'mutation productUpdate($companyId: Int!,$data: ProductUpdate!)
        {
            productUpdate(companyId: $companyId ,data: $data)
            {
                data
                {
                    ' . self::getProductSegment() . '
                    ' . self::getVariantSegment() . '
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('products/productUpdate', $query, $variables);
    }

    /**
     * Update a product image
     *
     * @param array $variables variables of the query
     *
     * @return true
     */
    public static function mutationProductImageUpdate(array $variables = [], $file = ''): bool
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

        return Curl::uploadImage($query, $variables, $file);
    }

    /**
     * Gets the information of a product
     *
     * @param array $variables variables of the query
     *
     * @return array information of the product
     * @throws Error
     */
    public static function queryProduct(array $variables = []): array
    {
        $query = 'query product($companyId: Int!,$productId: Int!)
        {
            product(companyId: $companyId,productId: $productId)
            {
                data
                {
                    ' . self::getProductSegment() . '
                    ' . self::getVariantSegment() . '           
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('products/product', $query, $variables);
    }

    /**
     * Gets all products
     * @param array $variables
     * @return array|bool
     * @throws Error
     */
    public static function queryProducts(array $variables = [])
    {
        $query = 'query products($companyId: Int!,$options: ProductOptions)
        {
            products(companyId: $companyId,options: $options)
            {
                data
                {
                    ' . self::getProductSegment() . '
                    ' . self::getVariantSegment() . '            
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

        return Curl::complex('products/products', $query, $variables, 'products');
    }

    //          PRIVATES          //

    /**
     * Product part of query
     *
     * @return string
     */
    private static function getProductSegment(): string
    {
        return '
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
            img
            deletable
            identifications
            {
                type
                favorite
                text
            }
            measurementUnit
            {
                measurementUnitId
                name
            }   
            warehouse
            {
                warehouseId
            }
            warehouses
            {
                warehouseId
                stock
                minStock
            }
            productCategory{
                name
                productCategoryId
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
                    fiscalZone
                }
                value
                ordering
            }
        ';
    }

    /**
     * Variant part of query
     *
     * @return string
     */
    private static function getVariantSegment(): string
    {
        return '
        variants
        {
            visible
            productId
            name
            reference
            summary
            price
            img
            priceWithTaxes
            hasStock
            stock
            deletable
            parent
            {
                productId
                name
            }
            warehouse
            {
                warehouseId
            }
            warehouses
            {
                warehouseId
                stock
                minStock
            }
            identifications
            {
                type
                favorite
                text
            } 
            propertyPairs
            {
                propertyId
                propertyValueId
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
        ';
    }
}
