<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Error;

class Currencies
{
    /**
     * Get All Currencies from Moloni ES
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryCurrencies($variables)
    {
        $query = 'query currencies($options: CurrencyOptions)
        {
            currencies(options: $options) 
            {
                errors
                {
                    field
                    msg
                }
                data
                {
                    currencyId
                    symbol
                    symbolPosition
                    numberDecimalPlaces
                    iso4217
                    largeCurrency
                    description
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
            }
        }';

        return Curl::complex('currencies/currencies', $query, $variables, 'currencies', false);
    }

    /**
     * Get All Currencies exchanges from Moloni ES
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryCurrencyExchanges($variables = [])
    {
        $query = 'query currencyExchanges($options: CurrencyExchangeOptions)
        {
            currencyExchanges(options: $options)
            {
                data
                {
                    currencyExchangeId
                    name
                    exchange
                    from
                    {
                        currencyId
                        iso4217
                    }
                    to
                    {
                        currencyId
                        iso4217
                    }
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::complex('currencies/currencyExchanges', $query, $variables, 'currencyExchanges', false);
    }
}