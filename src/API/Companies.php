<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Companies extends EndpointAbstract
{
    /**
     * Gets all the companies that the logged-in user has access
     *
     * @return array return and array with all-companies Ids
     *
     * @throws APIExeption
     */
    public static function queryMe(): array
    {
        $action = 'companies/me';

        $query = self::loadQuery('me');

        if (empty(self::$requestsCache[$action])) {
            self::$requestsCache[$action] = Curl::simple($action, $query);
        }

        return self::$requestsCache[$action];
    }

    /**
     * Gets the information of the companies that the logged-in user has access
     *
     * @param array|null $variables
     *
     * @return array returns an array with the company's information
     *
     * @throws APIExeption
     */
    public static function queryCompany(?array $variables = []): array
    {
        $query = self::loadQuery('company');

        return Curl::simple('company', $query, $variables);
    }
}
