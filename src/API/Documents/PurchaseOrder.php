<?php

namespace MoloniES\API\Documents;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class PurchaseOrder extends EndpointAbstract
{
    /**
     * Creates a purchase order
     *
     * @param array|null $variables variables of the request
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryPurchaseOrder(?array $variables = []): array
    {
        $query = self::loadQuery('purchaseOrder');

        return Curl::simple('purchaseOrder', $query, $variables);
    }

    /**
     * Gets all purchase orders
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryPurchaseOrders(?array $variables = []): array
    {
        $query = self::loadQuery('purchaseOrders');

        return Curl::complex('purchaseOrders', $query, $variables);
    }

    /**
     * Get document token and path for purchase orders
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryPurchaseOrderGetPDFToken(?array $variables = []): array
    {
        $query = self::loadQuery('purchaseOrderGetPDFToken');

        return Curl::simple('purchaseOrderGetPDFToken', $query, $variables);
    }

    /**
     * Creates a purchase order
     *
     * @param array|null $variables variables of the request
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationPurchaseOrderCreate(?array $variables = [])
    {
        $query = self::loadMutation('purchaseOrderCreate');

        return Curl::simple('purchaseOrderCreate', $query, $variables);
    }

    /**
     * Update a purchase order
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationPurchaseOrderUpdate(?array $variables = [])
    {
        $query = self::loadMutation('purchaseOrderUpdate');

        return Curl::simple('purchaseOrderUpdate', $query, $variables);
    }

    /**
     * Creates purchase order pdf
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function mutationPurchaseOrderGetPDF(?array $variables = []): array
    {
        $query = self::loadMutation('purchaseOrderGetPDF');

        return Curl::simple('purchaseOrderGetPDF', $query, $variables);
    }

    /**
     * Send purchased order by mail
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationPurchaseOrderSendMail(?array $variables = [])
    {
        $query = self::loadMutation('purchaseOrderSendMail');

        return Curl::simple('purchaseOrderSendMail', $query, $variables);
    }
}
