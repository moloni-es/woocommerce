<?php

namespace MoloniES\API\Documents;

use MoloniES\Curl;
use MoloniES\Exceptions\Error;

class Receipt
{
    /**
     * Gets receipt information
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryReceipt(?array $variables = [])
    {
        $query = 'query receipt($companyId: Int!,$documentId: Int!,$options: ReceiptOptionsSingle)
        {
            receipt(companyId: $companyId,documentId: $documentId,options: $options)
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

        return Curl::simple('documents/receipt', $query, $variables);
    }

    /**
     * Gets all receipts
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryReceipts(?array $variables = [])
    {
        $query = 'query receipts($companyId: Int!,$options: ReceiptOptions)
        {
            receipts(companyId: $companyId,options: $options)
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

        return Curl::complex('documents/receipts', $query, $variables, 'receipts');
    }

    /**
     * Get document token and path for receipts
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryReceiptGetPDFToken(?array $variables = [])
    {
        $query = 'query receiptGetPDFToken($documentId: Int!)
        {
            receiptGetPDFToken(documentId: $documentId)
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

        return Curl::simple('documents/receiptGetPDFToken', $query, $variables);
    }

    /**
     * Creates a receipt
     *
     * @param array|null $variables variables of the request
     *
     * @return mixed
     *
     * @throws Error
     */
    public static function mutationReceiptCreate(?array $variables = [])
    {
        $query = 'mutation receiptCreate($companyId: Int!,$data: ReceiptInsert!,$options: ReceiptMutateOptions)
        {
            receiptCreate(companyId: $companyId,data: $data,options: $options)
            {
                data
                {
                    documentId
                    number
                    entityVat
                    entityNumber
                    entityName
                    documentSetName
                    totalValue
                    currencyExchangeTotalValue
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('documents/receiptCreate', $query, $variables);
    }

    /**
     * Update a receipt
     *
     * @param array|null $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function mutationReceiptUpdate(?array $variables = [])
    {
        $query = 'mutation receiptUpdate($companyId: Int!,$data: ReceiptUpdate!)
        {
            receiptUpdate(companyId: $companyId,data: $data)
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

        return Curl::simple('documents/receiptUpdate', $query, $variables);
    }

    /**
     *Send receipt by mail
     *
     * @param array|null $variables
     *
     * @return mixed
     * @throws Error
     */
    public static function mutationReceiptSendMail(?array $variables = [])
    {
        $query = 'mutation receiptSendMail($companyId: Int!,$documents: [Int]!,$mailData: MailData)
        {
            receiptSendMail(companyId: $companyId,documents: $documents,mailData: $mailData)
        }';

        return Curl::simple('documents/receiptSendMail', $query, $variables);
    }

    /**
     * Creates receipt pdf
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function mutationReceiptGetPDF(?array $variables = [])
    {
        $query = 'mutation receiptGetPDF($companyId: Int!,$documentId: Int!)
        {
            receiptGetPDF(companyId: $companyId,documentId: $documentId)
        }';

        return Curl::simple('documents/receiptGetPDF', $query, $variables);
    }
}