<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Exceptions\Error;

class PropertyGroups
{
    /**
     * Get multiple property groups
     * @param $variables
     * @return array|bool
     * @throws Error
     */
    public static function queryPropertyGroups($variables = [])
    {
        $query = 'query propertyGroups($companyId: Int!,$options: PropertyGroupOptions)
        {
            propertyGroups(companyId: $companyId,options: $options) 
            {
                data
                {
                    propertyGroupId
                    name
                    visible
                    properties
                    {
                        propertyId
                        name
                        visible
                        ordering
                        values
                        {
                           propertyValueId
                           code
                           value
                           visible
                           ordering
                        }
                    }
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
     * @param $variables
     * @return array|bool
     * @throws Error
     */
    public static function queryPropertyGroup($variables = [])
    {
        $query = 'query propertyGroup($companyId: Int!,$propertyGroupId: String!)
        {
            propertyGroup(companyId: $companyId,propertyGroupId: $propertyGroupId)
            {
                data
                {
                    propertyGroupId
                    name
                    visible
                    properties
                    {
                        propertyId
                        name
                        visible
                        ordering
                        values
                        {
                           propertyValueId
                           code
                           value
                           visible
                           ordering
                        }
                    }
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
     * @param $variables
     * @return array|bool
     * @throws Error
     */
    public static function mutationPropertyGroupUpdate($variables = [])
    {
        $query = 'mutation propertyGroupUpdate($companyId: Int!,$data: PropertyGroupUpdate!)
        {
            propertyGroupUpdate(companyId: $companyId,data: $data)
            {
                data
                {
                    propertyGroupId
                    name
                    visible
                    properties
                    {
                        propertyId
                        name
                        visible
                        ordering
                        values
                        {
                           propertyValueId
                           code
                           value
                           visible
                           ordering
                        }
                    }
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
     * @param $variables
     * @return array
     * @throws Error
     */
    public static function mutationPropertyGroupCreate($variables = [])
    {
        $query = 'mutation propertyGroupCreate($companyId: Int!,$data: PropertyGroupInsert!)
        {
            propertyGroupCreate(companyId: $companyId,data: $data)
            {
                data
                {
                    propertyGroupId
                    name
                    visible
                    properties
                    {
                        propertyId
                        name
                        visible
                        ordering
                        values
                        {
                           propertyValueId
                           code
                           value
                           visible
                           ordering
                        }
                    }
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
}