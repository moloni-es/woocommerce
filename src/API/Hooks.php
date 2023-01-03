<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Error;

class Hooks
{

    /**
     * Gets all hooks
     *
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryHooks($variables = [])
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
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function mutationHookCreate($variables = [])
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
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function mutationHookDelete($variables = [])
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