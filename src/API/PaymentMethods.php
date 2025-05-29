<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class PaymentMethods extends EndpointAbstract
{
    /**
     * Get payment methods info
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws APIExeption
     */
    public static function queryPaymentMethod(?array $variables = []): array
    {
        $query = self::loadQuery('paymentMethod');

        return Curl::simple('paymentMethod', $query, $variables);
    }

    /**
     * Get All Payment Methods from Moloni ES
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryPaymentMethods(?array $variables = []): array
    {
        $action = 'paymentMethods';

        $query = self::loadQuery($action);

        if (empty(self::$requestsCache[$action])) {
            self::$requestsCache[$action] = Curl::complex($action, $query, $variables);
        }

        return self::$requestsCache[$action];
    }

    /**
     * Creates a payment method
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationPaymentMethodCreate(?array $variables = [])
    {
        $query = self::loadMutation('paymentMethodCreate');

        return Curl::simple('paymentMethodCreate', $query, $variables);
    }
}
