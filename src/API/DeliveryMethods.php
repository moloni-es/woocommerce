<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class DeliveryMethods extends EndpointAbstract
{
    /**
     * Create a new delivery methods
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationDeliveryMethodCreate(?array $variables = []) {
        $query = self::loadMutation('deliveryMethodCreate');

        return Curl::simple('deliveryMethodCreate', $query, $variables);
    }

    /**
     * Get All DeliveryMethods from Moloni ES
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws APIExeption
     */
    public static function queryDeliveryMethods(?array $variables = []): array
    {
        $query = self::loadQuery('deliveryMethods');

        return Curl::complex('deliveryMethods', $query, $variables);
    }
}
