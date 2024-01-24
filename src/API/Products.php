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
     * @param array|null $variables
     *
     * @return array returns some data of the updated product
     *
     * @throws APIExeption
     */
    public static function mutationProductUpdate(?array $variables = []): ?array
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
     *
     * @param array|null $variables
     *
     * @return array
     * @throws APIExeption
     */
    public static function queryProducts(array $variables = []): ?array
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

        return Curl::simple('products/products', $query, $variables);
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
