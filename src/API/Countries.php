<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Countries extends EndpointAbstract
{
    /**
     * Gets all countries
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryCountries(?array $variables = []): array
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

        return Curl::simple('countries/countries', $query, $variables);
    }

    /**
     * Gets country info
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws APIExeption
     */
    public static function queryCountry(?array $variables = []): array
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
