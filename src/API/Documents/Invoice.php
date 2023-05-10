<?php

namespace MoloniES\API\Documents;

use MoloniES\Curl;
use MoloniES\Exceptions\Error;

class Invoice
{
    /**
     * Gets invoice information
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryInvoice(?array $variables = [])
    {
        $query = 'query invoice($companyId: Int!,$documentId: Int!,$options: InvoiceOptionsSingle)
        {
            invoice(companyId: $companyId,documentId: $documentId,options: $options)
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

        return Curl::simple('documents/invoice', $query, $variables);
    }

    /**
     * Gets all invoices
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryInvoices(?array $variables = [])
    {
        $query = 'query invoices($companyId: Int!,$options: InvoiceOptions)
        {
            invoices(companyId: $companyId,options: $options)
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

        return Curl::complex('documents/invoices', $query, $variables, 'invoices');
    }

    /**
     * Get document token and path for invoices
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryInvoiceGetPDFToken(?array $variables = [])
    {
        $query = 'query invoiceGetPDFToken($documentId: Int!)
        {
            invoiceGetPDFToken(documentId: $documentId)
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

        return Curl::simple('documents/invoiceGetPDFToken', $query, $variables);
    }

    /**
     * Creates invoice pdf
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function mutationInvoiceGetPDF(array $variables = []): array
    {
        $query = 'mutation invoiceGetPDF($companyId: Int!,$documentId: Int!)
        {
            invoiceGetPDF(companyId: $companyId,documentId: $documentId)
        }';

        return Curl::simple('documents/invoiceGetPDF', $query, $variables);
    }

    /**
     * Creates an invoice
     *
     * @param array|null $variables variables of the request
     *
     * @return mixed
     *
     * @throws Error
     */
    public static function mutationInvoiceCreate(?array $variables = [])
    {
        $query = 'mutation invoiceCreate($companyId: Int!,$data: InvoiceInsert!,$options: InvoiceMutateOptions)
        {
            invoiceCreate(companyId: $companyId,data: $data,options: $options) 
            {
                errors
                {
                    field
                    msg
                }
                data{
                    documentId
                    number
                    totalValue
                    currencyExchangeTotalValue
                    documentTotal
                    documentSetName
                    ourReference
                    products
                    {
                        productId
                        documentProductId
                    }
                }
            }
        }';

        return Curl::simple('documents/invoiceCreate', $query, $variables);
    }

    /**
     * Update an invoice
     *
     * @param array|null $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function mutationInvoiceUpdate(?array $variables = [])
    {
        $query = 'mutation invoiceUpdate($companyId: Int!,$data: InvoiceUpdate!)
        {
            invoiceUpdate(companyId: $companyId,data: $data) 
            {
                errors
                {
                    field
                    msg
                }
                data
                {
                    documentId
                    status                              
                }
            }
        }';

        return Curl::simple('documents/invoiceUpdate', $query, $variables);
    }

    /**
     * Send invoice by mail
     *
     * @param array|null $variables
     *
     * @return mixed
     * @throws Error
     */
    public static function mutationInvoiceSendMail(?array $variables = [])
    {
        $query = 'mutation invoiceSendMail($companyId: Int!,$documents: [Int]!,$mailData: MailData)
        {
            invoiceSendMail(companyId: $companyId,documents: $documents,mailData: $mailData)
        }';

        return Curl::simple('documents/invoiceSendMail', $query, $variables);
    }
}