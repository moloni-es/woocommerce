<?php


namespace MoloniES\API;


use MoloniES\Curl;
use MoloniES\Error;

class Languages
{
    /**
     * Gets languages.
     *
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryLanguage($variables = [])
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

        return Curl::simple('languages/language', $query, $variables, false);
    }

    /**
     * Gets language info
     *
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryLanguages($variables = [])
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

        return Curl::complex('languages/languages', $query, $variables, 'languages', false);
    }
}