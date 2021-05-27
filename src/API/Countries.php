<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Error;

class Countries
{
    /**
     * Gets all countries
     *
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryCountries($variables = [])
    {
        $query = 'query countries($options: CountryOptions)
        {
            countries(options: $options)
            {
                data
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

        return Curl::complex('countries/countries', $query, $variables, 'countries');
    }

    /**
     * Gets country info
     *
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryCountry($variables = [])
    {
        $query = 'query country($countryId: Int!)
        {
            country(countryId: $countryId)
            {
                data
                {
                    countryId
                    iso3166_1
                    language
                    {
                        languageId
                    }
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('countries/country', $query, $variables);
    }
}