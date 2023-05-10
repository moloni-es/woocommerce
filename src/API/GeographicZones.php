<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Exceptions\Error;

class GeographicZones
{
    /**
     * Gets geographic zones
     *
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryGeographicZones($variables = [])
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