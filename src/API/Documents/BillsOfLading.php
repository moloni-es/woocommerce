<?php

namespace MoloniES\API\Documents;

use MoloniES\Curl;
use MoloniES\Exceptions\Error;

class BillsOfLading
{
    /**
     * Creates a bill of lading
     *
     * @param array|null $variables variables of the request
     *
     * @return mixed
     *
     * @throws Error
     */
    public static function mutationBillsOfLadingCreate(?array $variables = [])
    {
        $query = 'mutation billsOfLadingCreate($companyId: Int!,$data: BillsOfLadingInsert!, $options: BillsOfLadingMutateOptions)
        {
            billsOfLadingCreate(companyId: $companyId,data: $data,options: $options) 
            {
                errors
                {
                    field
                    msg
                }
                data
                {
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

        return Curl::simple('documents/billsOfLadingCreate', $query, $variables);
    }

    /**
     * Creates a bill of lading
     * @param array|null $variables
     * @return mixed
     * @throws Error
     */
    public static function mutationBillsOfLadingUpdate(?array $variables = [])
    {
        $query = 'mutation billsOfLadingUpdate($companyId: Int!,$data: BillsOfLadingUpdate!)
        {
            billsOfLadingUpdate(companyId: $companyId,data: $data)
            {
                errors
                {
                    field
                    msg
                }
                data
                {
                    documentId
                    number
                    totalValue
                    documentTotal
                    documentSetName
                    ourReference
                }
            }
        }';

        return Curl::simple('documents/billsOfLadingUpdate', $query, $variables);
    }

    /**
     * Get document token and path for bills of lading
     *
     * @param array|null $variables
     *
     * @return mixed
     * @throws Error
     */
    public static function queryBillsOfLadingGetPDFToken(?array $variables = [])
    {
        $query = 'query billsOfLadingGetPDFToken($documentId: Int!)
        {
            billsOfLadingGetPDFToken(documentId: $documentId)
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

        return Curl::simple('documents/billsOfLadingGetPDFToken', $query, $variables);
    }

    /**
     * Creates bills of lading pdf
     *
     * @param array|null $variables
     *
     * @return mixed
     * @throws Error
     */
    public static function mutationBillsOfLadingGetPDF(?array $variables = [])
    {
        $query = 'mutation billsOfLadingGetPDF($companyId: Int!,$documentId: Int!)
        {
            billsOfLadingGetPDF(companyId: $companyId,documentId: $companyId)
        }';

        return Curl::simple('documents/billsOfLadingGetPDF', $query, $variables);
    }

    /**
     * Send bill of lading by email
     *
     * @param array|null $variables
     *
     * @return mixed
     * @throws Error
     */
    public static function mutationBillsOfLadingSendMail(?array $variables = [])
    {
        $query = 'mutation billsOfLadingSendMail($companyId: Int!,$documents: [Int]!,$mailData: MailData)
        {
            billsOfLadingSendMail(companyId: companyId,documents: $documents,mailData: $mailData)
        }';

        return Curl::simple('documents/billsOfLadingSendMail', $query, $variables);
    }
}