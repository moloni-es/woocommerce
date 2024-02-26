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
        $query = 'query geographicZones($companyId: Int!,$options: GeographicZoneOptions)
        {
            geographicZones(companyId: $companyId,options: $options)
            {
                data
                {
                    geographicZoneId
                    name
                    abbreviation
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

        return Curl::complex('geographiczones/geographicZones', $query, $variables, 'geographicZones');
    }
}
