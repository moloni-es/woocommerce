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
        $query = 'query timezones($options: TimezoneOptions)
        {
            timezones(options: $options) 
            {
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
                data{
                    timezoneId
                    name
                    visible
                    ordering
                    tzName
                    offset
                    country
                    {
                           countryId
                           iso3166_1
                           title
                           language
                           {
                                    languageId
                                    name
                           } 
                    }
                }
            }
        }';

        return Curl::complex('timezones/timezones', $query, $variables, 'timezones');
    }
}
