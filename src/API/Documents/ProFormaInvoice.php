<?php

namespace MoloniES\API\Documents;

use MoloniES\Curl;
use MoloniES\Exceptions\Error;

class ProFormaInvoice
{
    /**
     * Creates a pro forma invoice
     *
     * @param array|null $variables variables of the request
     *
     * @return void Api data
     * @throws Error
     */
    public static function queryProFormaInvoice(?array $variables = [])
    {
        $query = 'query proFormaInvoice($companyId: Int!,$documentId: Int!,$options: ProFormaInvoiceOptionsSingle)
        {
            proFormaInvoice(companyId: $companyId,documentId: $documentId,options: $options)
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

        return Curl::simple('documents/proFormaInvoice', $query, $variables);
    }

    /**
     * Gets all pro forma invoices
     *
     * @param array|null $variables
     *
     * @return array|bool Api data
     * @throws Error
     */
    public static function queryProFormaInvoices(?array $variables = [])
    {
        $query = 'query proFormaInvoices($companyId: Int!,$options: ProFormaInvoiceOptions)
        {
            proFormaInvoices(companyId: $companyId,options: $options)
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

        return Curl::complex('documents/proFormaInvoices', $query, $variables, 'proFormaInvoices');
    }

    /**
     * Get document token and path for pro forma invoices
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryProFormaInvoiceGetPDFToken(?array $variables = [])
    {
        $query = 'query proFormaInvoiceGetPDFToken($documentId: Int!)
        {
            proFormaInvoiceGetPDFToken(documentId: $documentId)
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

        return Curl::simple('documents/proFormaInvoiceGetPDFToken', $query, $variables);
    }

    /**
     * Creates a pro forma invoice
     *
     * @param array|null $variables variables of the request
     *
     * @return mixed
     *
     * @throws Error
     */
    public static function mutationProFormaInvoiceCreate(?array $variables = [])
    {
        $query = 'mutation proFormaInvoiceCreate($companyId: Int!,$data: ProFormaInvoiceInsert!,$options: ProFormaInvoiceMutateOptions)
        {
            proFormaInvoiceCreate(companyId: $companyId,data: $data,options: $options)
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

        return Curl::simple('documents/proFormaInvoiceCreate', $query, $variables);
    }

    /**
     * Update a pro forma invoice
     *
     * @param array|null $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function mutationProFormaInvoiceUpdate(?array $variables = [])
    {
        $query = 'mutation proFormaInvoiceUpdate($companyId: Int!,$data: ProFormaInvoiceUpdate!)
        {
            proFormaInvoiceUpdate(companyId: $companyId,data: $data)
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

        return Curl::simple('documents/proFormaInvoiceUpdate', $query, $variables);
    }

    /**
     * Creates pro forma invocie pdf
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function mutationProFormaInvoiceGetPDF(?array $variables = [])
    {
        $query = 'mutation proFormaInvoiceGetPDF($companyId: Int!,$documentId: Int!)
        {
            proFormaInvoiceGetPDF(companyId: $companyId,documentId: $documentId)
        }';

        return Curl::simple('documents/proFormaInvoiceGetPDF', $query, $variables);
    }

    /**
     * Send pro forma invoice by mail
     *
     * @param array|null $variables
     *
     * @return mixed
     * @throws Error
     */
    public static function mutationProFormaInvoiceSendMail(?array $variables = [])
    {
        $query = 'mutation proFormaInvoiceSendMail($companyId: Int!,$documents: [Int]!,$mailData: MailData)
        {
            proFormaInvoiceSendMail(companyId: $companyId,documents: $documents,mailData: $mailData)
        }';

        return Curl::simple('documents/proFormaInvoiceSendMail', $query, $variables);
    }
}