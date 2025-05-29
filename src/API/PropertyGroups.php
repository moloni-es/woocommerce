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
        $query = self::loadQuery('propertyGroups');

        return Curl::complex('propertyGroups', $query, $variables);
    }

    /**
     * Get a single property group
     *
     * @param array $variables
     *
     * @return array|bool
     *
     * @throws APIExeption
     */
    public static function queryPropertyGroup(array $variables = [])
    {
        $query = self::loadQuery('propertyGroup');

        return Curl::simple('propertyGroup', $query, $variables);
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
        $query = self::loadMutation('propertyGroupUpdate');

        return Curl::simple('propertyGroupUpdate', $query, $variables);
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
        $query = self::loadMutation('propertyGroupCreate');

        return Curl::simple('propertyGroupCreate', $query, $variables);
    }
}
