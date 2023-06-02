<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;
use MoloniES\API\Abstracts\EndpointAbstract;

class Currencies extends EndpointAbstract
{
    /**
     * Get All Currencies from Moloni ES
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryCurrencies(?array $variables = []): array
    {
        $action = 'currencies/currencies';

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

        if (empty(self::$cache[$action])) {
            self::$cache[$action] = Curl::complex($action, $query, $variables, 'currencies');
        }

        return self::$cache[$action];
    }

    /**
     * Get All Currencies exchanges from Moloni ES
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws APIExeption
     */
    public static function queryCurrencyExchanges(?array $variables = []): array
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

        return Curl::complex('currencies/currencyExchanges', $query, $variables, 'currencyExchanges');
    }
}
