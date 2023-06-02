<?php

namespace MoloniES\API\Documents;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Estimate extends EndpointAbstract
{
    /**
     * Gets estimate information
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function queryEstimate(?array $variables = []): array
    {
        $query = 'query estimate($companyId: Int!,$documentId: Int!,$options: EstimateOptionsSingle)
        {
            estimate(companyId: $companyId,documentId: $documentId,options: $options)
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
                    pdfExport
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('estimate/estimate', $query, $variables);
    }

    /**
     * Get document token and path for estimates
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function queryEstimateGetPDFToken(?array $variables = []): array
    {
        $query = 'query estimateGetPDFToken($documentId: Int!)
        {
            estimateGetPDFToken(documentId: $documentId)
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

        return Curl::simple('estimate/estimateGetPDFToken', $query, $variables);
    }

    /**
     * Creates an estimate
     *
     * @param array|null $variables variables of the request
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationEstimateCreate(?array $variables = []): array
    {
        $query = 'mutation estimateCreate($companyId: Int!,$data: EstimateInsert!,$options: EstimateMutateOptions){
            estimateCreate(companyId: $companyId,data: $data,options: $options) {
                errors{
                    field
                    msg
                }
                data{
                    documentId
                    number
                    totalValue
                    documentTotal
                    documentSetName
                    ourReference
                    currencyExchangeTotalValue
                    products
                    {
                        documentProductId
                        productId
                    }
                }
            }
        }';

        return Curl::simple('estimate/estimateCreate', $query, $variables);
    }

    /**
     * Update an estimate
     *
     * @param array|null $variables variables of the request
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationEstimateUpdate(?array $variables = []): array
    {
        $query = 'mutation estimateUpdate($companyId: Int!,$data: EstimateUpdate!)
        {
            estimateUpdate(companyId: $companyId,data: $data) 
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
                    currencyExchangeTotalValue
                    products
                    {
                        documentProductId
                        productId
                    }                              
                }
            }
        }';

        return Curl::simple('estimate/estimateUpdate', $query, $variables);
    }

    /**
     * Creates estimate pdf
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationEstimateGetPDF(?array $variables = []): array
    {
        $query = 'mutation estimateGetPDF($companyId: Int!,$documentId: Int!)
        {
            estimateGetPDF(companyId: $companyId,documentId: $documentId)
        }';

        return Curl::simple('estimate/estimateGetPDF', $query, $variables);
    }

    /**
     * Send estimate by mail
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationEstimateSendMail(?array $variables = [])
    {
        $query = 'mutation estimateSendMail($companyId: Int!,$documents: [Int]!,$mailData: MailData)
        {
            estimateSendMail(companyId: $companyId,documents: $documents,mailData: $mailData)
        }';

        return Curl::simple('estimate/estimateSendMail', $query, $variables);
    }
}
