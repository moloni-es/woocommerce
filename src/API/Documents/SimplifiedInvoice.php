<?php

namespace MoloniES\API\Documents;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class SimplifiedInvoice extends EndpointAbstract
{
    /**
     * Gets simplified invoice information
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws APIExeption
     */
    public static function querySimplifiedInvoice(?array $variables = []): array
    {
        $query = self::loadQuery('simplifiedInvoice');

        return Curl::simple('simplifiedInvoice', $query, $variables);
    }

    /**
     * Gets all simplified invoices
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws APIExeption
     */
    public static function querySimplifiedInvoices(?array $variables = []): array
    {
        $query = self::loadQuery('simplifiedInvoices');

        return Curl::complex('simplifiedInvoices', $query, $variables);
    }

    /**
     * Get document token and path for simplified invoices
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws APIExeption
     */
    public static function querySimplifiedInvoiceGetPDFToken(?array $variables = []): array
    {
        $query = self::loadQuery('simplifiedInvoiceGetPDFToken');

        return Curl::simple('simplifiedInvoiceGetPDFToken', $query, $variables);
    }

    /**
     * Creates a simplified invoice
     *
     * @param array|null $variables variables of the request
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationSimplifiedInvoiceCreate(?array $variables = [])
    {
        $query = self::loadMutation('simplifiedInvoiceCreate');

        return Curl::simple('simplifiedInvoiceCreate', $query, $variables);
    }

    /**
     * Update a simplified invoice
     *
     * @param array|null $variables variables of the request
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function mutationSimplifiedInvoiceUpdate(?array $variables = []): array
    {
        $query = self::loadMutation('simplifiedInvoiceUpdate');

        return Curl::simple('simplifiedInvoiceUpdate', $query, $variables);
    }

    /**
     * Creates simplified invoice pdf
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function mutationSimplifiedInvoiceGetPDF(?array $variables = []): array
    {
        $query = self::loadMutation('simplifiedInvoiceGetPDF');

        return Curl::simple('simplifiedInvoiceGetPDF', $query, $variables);
    }

    /**
     * Send simplified invoice by mail
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationSimplifiedInvoiceSendMail(?array $variables = [])
    {
        $query = self::loadMutation('simplifiedInvoiceSendMail');

        return Curl::simple('simplifiedInvoiceSendMail', $query, $variables);
    }
}
