<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Timezones extends EndpointAbstract
{
    /**
     * Get All Timezones from Moloni ES
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws APIExeption
     */
    public static function queryTimezones(?array $variables = []): array
    {
        $query = self::loadQuery('timezones');

        return Curl::complex('timezones', $query, $variables);
    }
}
