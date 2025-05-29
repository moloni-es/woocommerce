<?php

namespace MoloniES\API\Documents;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Receipt extends EndpointAbstract
{
    /**
     * Gets receipt information
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryReceipt(?array $variables = []): array
    {
        $query = self::loadQuery('receipt');

        return Curl::simple('receipt', $query, $variables);
    }

    /**
     * Gets all receipts
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryReceipts(?array $variables = []): array
    {
        $query = self::loadQuery('receipts');

        return Curl::complex('receipts', $query, $variables);
    }

    /**
     * Get document token and path for receipts
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryReceiptGetPDFToken(?array $variables = []): array
    {
        $query = self::loadQuery('receiptGetPDFToken');

        return Curl::simple('receiptGetPDFToken', $query, $variables);
    }

    /**
     * Creates a receipt
     *
     * @param array|null $variables variables of the request
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationReceiptCreate(?array $variables = [])
    {
        $query = self::loadMutation('receiptCreate');

        return Curl::simple('receiptCreate', $query, $variables);
    }

    /**
     * Update a receipt
     *
     * @param array|null $variables variables of the request
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function mutationReceiptUpdate(?array $variables = []): array
    {
        $query = self::loadMutation('receiptUpdate');

        return Curl::simple('receiptUpdate', $query, $variables);
    }

    /**
     *Send receipt by mail
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationReceiptSendMail(?array $variables = [])
    {
        $query = self::loadMutation('receiptSendMail');

        return Curl::simple('receiptSendMail', $query, $variables);
    }

    /**
     * Creates receipt pdf
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function mutationReceiptGetPDF(?array $variables = []): array
    {
        $query = self::loadMutation('receiptGetPDF');

        return Curl::simple('receiptGetPDF', $query, $variables);
    }
}
