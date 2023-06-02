<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class PropertyGroups extends EndpointAbstract
{
    /**
     * Get multiple property groups
     *
     * @param array|null $variables
     *
     * @return array
     *
     * @throws APIExeption
     */
    public static function queryPropertyGroups(?array $variables = []): array
    {
        $query = 'query propertyGroups($companyId: Int!,$options: PropertyGroupOptions)
        {
            propertyGroups(companyId: $companyId,options: $options) 
            {
                data
                {
                    ' . self::getCommonSegment() . '
                }
                errors
                {
                    field
                    msg
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
            }
        }';

        return Curl::complex('propertygroup/propertyGroups', $query, $variables, 'propertyGroups');
    }

    /**
     * Get single property group
     *
     * @param array $variables
     *
     * @return array|bool
     *
     * @throws APIExeption
     */
    public static function queryPropertyGroup(array $variables = [])
    {
        $query = 'query propertyGroup($companyId: Int!,$propertyGroupId: String!)
        {
            propertyGroup(companyId: $companyId,propertyGroupId: $propertyGroupId)
            {
                data
                {
                    ' . self::getCommonSegment() . '
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('propertygroup/propertyGroup', $query, $variables);
    }

    /**
     * Update a property group
     *
     * @param array $variables
     *
     * @return array|bool
     *
     * @throws APIExeption
     */
    public static function mutationPropertyGroupUpdate(array $variables = [])
    {
        $query = 'mutation propertyGroupUpdate($companyId: Int!,$data: PropertyGroupUpdate!)
        {
            propertyGroupUpdate(companyId: $companyId,data: $data)
            {
                data
                {
                    ' . self::getCommonSegment() . '
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('propertygroup/propertyGroupUpdate', $query, $variables);
    }

    /**
     * Create a property group
     *
     * @param array $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationPropertyGroupCreate(array $variables = [])
    {
        $query = 'mutation propertyGroupCreate($companyId: Int!,$data: PropertyGroupInsert!)
        {
            propertyGroupCreate(companyId: $companyId,data: $data)
            {
                data
                {
                    ' . self::getCommonSegment() . '
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('propertygroup/propertyGroupCreate', $query, $variables);
    }

    /**
     * Common segments for all queries and mutations
     *
     * @return string
     */
    private static function getCommonSegment(): string
    {
        return '
            propertyGroupId
            name
            visible
            deletable
            properties
            {
                propertyId
                name
                visible
                ordering
                deletable
                values
                {
                   propertyValueId
                   code
                   value
                   visible
                   ordering
                   deletable
                }
            }
        ';
    }
}
