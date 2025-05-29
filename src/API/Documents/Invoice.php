<?php

namespace MoloniES\API\Documents;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Invoice extends EndpointAbstract
{
    /**
     * Gets invoice information
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryInvoice(?array $variables = []): array
    {
        $query = self::loadQuery('invoice');

        return Curl::simple('invoice', $query, $variables);
    }

    /**
     * Gets all invoices
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryInvoices(?array $variables = []): array
    {
        $query = self::loadQuery('invoices');

        return Curl::complex('invoices', $query, $variables);
    }

    /**
     * Get document token and path for invoices
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryInvoiceGetPDFToken(?array $variables = []): array
    {
        $query = self::loadQuery('invoiceGetPDFToken');

        return Curl::simple('invoiceGetPDFToken', $query, $variables);
    }

    /**
     * Creates invoice pdf
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function mutationInvoiceGetPDF(array $variables = []): array
    {
        $query = self::loadMutation('invoiceGetPDF');

        return Curl::simple('invoiceGetPDF', $query, $variables);
    }

    /**
     * Creates an invoice
     *
     * @param array|null $variables variables of the request
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationInvoiceCreate(?array $variables = [])
    {
        $query = self::loadMutation('invoiceCreate');

        return Curl::simple('invoiceCreate', $query, $variables);
    }

    /**
     * Update an invoice
     *
     * @param array|null $variables variables of the request
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function mutationInvoiceUpdate(?array $variables = []): array
    {
        $query = self::loadMutation('invoiceUpdate');

        return Curl::simple('invoiceUpdate', $query, $variables);
    }

    /**
     * Send invoice by mail
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationInvoiceSendMail(?array $variables = [])
    {
        $query = self::loadMutation('invoiceSendMail');

        return Curl::simple('invoiceSendMail', $query, $variables);
    }
}
