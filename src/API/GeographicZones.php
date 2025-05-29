<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class GeographicZones extends EndpointAbstract
{
    /**
     * Gets geographic zones
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws APIExeption
     */
    public static function queryGeographicZones(?array $variables = []): array
    {
        $query = self::loadQuery('geographicZones');

        return Curl::complex('geographicZones', $query, $variables);
    }
}
