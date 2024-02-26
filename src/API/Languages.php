<?php


namespace MoloniES\API;


use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Languages extends EndpointAbstract
{
    /**
     * Gets languages.
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws APIExeption
     */
    public static function queryLanguage(?array $variables = []): array
    {
        $query = 'query language($languageId: Int!)
        {
            language(languageId: $languageId)
            {
                data
                {
                    languageId
                    name
                    iso3166
                    flag
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('languages/language', $query, $variables);
    }

    /**
     * Gets language info
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws APIExeption
     */
    public static function queryLanguages(?array $variables = []): array
    {
        $query = 'query languages($options: LanguageOptions)
        {
            languages(options: $options)
            {
                data
                {
                    languageId
                    name
                    iso3166
                    flag
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

        return Curl::complex('languages/languages', $query, $variables, 'languages');
    }
}
