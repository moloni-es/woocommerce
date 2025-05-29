<?php

namespace MoloniES\API\Documents;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class ProFormaInvoice extends EndpointAbstract
{
    /**
     * Creates a pro forma invoice
     *
     * @param array|null $variables variables of the request
     *
     * @return void Api data
     *
     * @throws APIExeption
     */
    public static function queryProFormaInvoice(?array $variables = [])
    {
        $query = self::loadQuery('proFormaInvoice');

        return Curl::simple('proFormaInvoice', $query, $variables);
    }

    /**
     * Gets all pro forma invoices
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryProFormaInvoices(?array $variables = []): array
    {
        $query = self::loadQuery('proFormaInvoices');

        return Curl::complex('proFormaInvoices', $query, $variables);
    }

    /**
     * Get document token and path for pro forma invoices
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryProFormaInvoiceGetPDFToken(?array $variables = []): array
    {
        $query = self::loadQuery('proFormaInvoiceGetPDFToken');

        return Curl::simple('proFormaInvoiceGetPDFToken', $query, $variables);
    }

    /**
     * Creates a pro forma invoice
     *
     * @param array|null $variables variables of the request
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationProFormaInvoiceCreate(?array $variables = [])
    {
        $query = self::loadMutation('proFormaInvoiceCreate');

        return Curl::simple('proFormaInvoiceCreate', $query, $variables);
    }

    /**
     * Update a pro forma invoice
     *
     * @param array|null $variables variables of the request
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function mutationProFormaInvoiceUpdate(?array $variables = []): array
    {
        $query = self::loadMutation('proFormaInvoiceUpdate');

        return Curl::simple('proFormaInvoiceUpdate', $query, $variables);
    }

    /**
     * Creates pro forma invocie pdf
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function mutationProFormaInvoiceGetPDF(?array $variables = []): array
    {
        $query = self::loadMutation('proFormaInvoiceGetPDF');

        return Curl::simple('proFormaInvoiceGetPDF', $query, $variables);
    }

    /**
     * Send pro forma invoice by mail
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationProFormaInvoiceSendMail(?array $variables = [])
    {
        $query = self::loadMutation('proFormaInvoiceSendMail');

        return Curl::simple('proFormaInvoiceSendMail', $query, $variables);
    }
}
