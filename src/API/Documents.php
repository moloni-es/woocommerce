<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Documents extends EndpointAbstract
{
    /**
     * Gets documents info by id
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function queryDocument(?array $variables = [])
    {
        $query = self::loadQuery('document');

        return Curl::simple('document', $query, $variables);
    }
}
