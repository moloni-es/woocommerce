<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Error;

class Timezones
{
    /**
     * Get All Timezones from Moloni ES
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryTimezones($variables)
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

        return Curl::complex('timezones/timezones', $query, $variables, 'timezones', false);
    }
}