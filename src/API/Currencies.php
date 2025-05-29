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
        $action = 'currencies';

        $query = self::loadQuery($action);

        if (empty(self::$requestsCache[$action])) {
            self::$requestsCache[$action] = Curl::complex($action, $query, $variables);
        }

        return self::$requestsCache[$action];
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
        $query = self::loadQuery('currencyExchanges');

        return Curl::complex('currencyExchanges', $query, $variables);
    }
}
