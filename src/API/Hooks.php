<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Hooks extends EndpointAbstract
{

    /**
     * Gets all hooks
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryHooks(?array $variables = []): array
    {
        $query = 'query hooks($companyId: Int!,$options: HookOptions)
        {
            hooks(companyId: $companyId,options: $options)
            {
                data
                {
                    hookId
                    url
                    name
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::complex('hooks/hooks', $query, $variables, 'hooks');
    }

    /**
     * Create a hook
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function mutationHookCreate(?array $variables = []): array
    {
        $query = 'mutation hookCreate($companyId: Int!,$data: HookInsert!)
        {
            hookCreate(companyId: $companyId,data: $data)
            {
                data
                {
                    hookId
                    url
                    name
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('hooks/hookCreate', $query, $variables);
    }

    /**
     * Delete hooks
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function mutationHookDelete(?array $variables = []): array
    {
        $query = 'mutation hookDelete($companyId: Int!,$hookId: [String!]!)
        {
            hookDelete(companyId: $companyId,hookId: $hookId)
            {
                status
                deletedCount
                elementsCount
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('hooks/hookDelete', $query, $variables);
    }
}
