<?php

namespace MoloniES\API\Documents;

use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class SimplifiedInvoice
{
    /**
     * Gets simplified invoice information
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws APIExeption
     */
    public static function querySimplifiedInvoice(?array $variables = [])
    {
        $query = 'query simplifiedInvoice($companyId: Int!,$documentId: Int!,$options: SimplifiedInvoiceOptionsSingle)
        {
            simplifiedInvoice(companyId: $companyId,documentId: $documentId,options: $options)
            {
                data
                {
                    documentId
                    number
                    ourReference
                    yourReference
                    entityVat
                    entityNumber
                    entityName
                    documentSetName
                    totalValue
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('documents/simplifiedInvoice', $query, $variables);
    }

    /**
     * Gets all simplified invoices
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws APIExeption
     */
    public static function querySimplifiedInvoices(?array $variables = [])
    {
        $query = 'query simplifiedInvoices($companyId: Int!,$options: SimplifiedInvoiceOptions)
        {
            simplifiedInvoices(companyId: $companyId,options: $options)
            {
                data
                {
                    documentId
                    number
                    ourReference
                    yourReference
                    entityVat
                    entityNumber
                    entityName
                    documentSetName
                    totalValue
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
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::complex('documents/simplifiedInvoices', $query, $variables, 'simplifiedInvoices');
    }

    /**
     * Get document token and path for simplified invoices
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws APIExeption
     */
    public static function querySimplifiedInvoiceGetPDFToken(?array $variables = [])
    {
        $query = 'query simplifiedInvoiceGetPDFToken($documentId: Int!)
        {
            simplifiedInvoiceGetPDFToken(documentId: $documentId)
            {
                data
                {
                    token
                    filename
                    path
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('documents/simplifiedInvoiceGetPDFToken', $query, $variables);
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
        $query = 'mutation simplifiedInvoiceCreate($companyId: Int!,$data: SimplifiedInvoiceInsert!,$options: SimplifiedInvoiceMutateOptions)
        {
            simplifiedInvoiceCreate(companyId: $companyId,data: $data,options: $options)
            {
                data
                {
                    documentId
                    number
                    ourReference
                    yourReference
                    entityVat
                    entityNumber
                    entityName
                    documentSetName
                    totalValue
                    currencyExchangeTotalValue
                    products
                    {
                        productId
                        documentProductId
                    }
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('documents/simplifiedInvoiceCreate', $query, $variables);
    }

    /**
     * Update a simplified invoice
     *
     * @param array|null $variables variables of the request
     *
     * @return array Api data
     * @throws APIExeption
     */
    public static function mutationSimplifiedInvoiceUpdate(?array $variables = [])
    {
        $query = 'mutation simplifiedInvoiceUpdate($companyId: Int!,$data: SimplifiedInvoiceUpdate!)
        {
            simplifiedInvoiceUpdate(companyId: $companyId,data: $data)
            {
                data
                {
                    documentId
                    status
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('documents/simplifiedInvoiceUpdate', $query, $variables);
    }

    /**
     * Creates simplified invoice pdf
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws APIExeption
     */
    public static function mutationSimplifiedInvoiceGetPDF(?array $variables = [])
    {
        $query = 'mutation simplifiedInvoiceGetPDF($companyId: Int!,$documentId: Int!)
        {
            simplifiedInvoiceGetPDF(companyId: $companyId,documentId: $documentId)
        }';

        return Curl::simple('documents/simplifiedInvoiceGetPDF', $query, $variables);
    }

    /**
     * Send simplified invoice by mail
     *
     * @param array|null $variables
     *
     * @return mixed
     * @throws APIExeption
     */
    public static function mutationSimplifiedInvoiceSendMail(?array $variables = [])
    {
        $query = 'mutation simplifiedInvoiceSendMail($companyId: Int!,$documents: [Int]!,$mailData: MailData)
        {
            simplifiedInvoiceSendMail(companyId: $companyId,documents: $documents,mailData: $mailData)
        }';

        return Curl::simple('documents/simplifiedInvoiceSendMail', $query, $variables);
    }
}
