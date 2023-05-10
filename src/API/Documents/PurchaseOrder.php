<?php

namespace MoloniES\API\Documents;

use MoloniES\Curl;
use MoloniES\Exceptions\Error;

class PurchaseOrder
{
    /**
     * Creates a purchase order
     *
     * @param array|null $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryPurchaseOrder(?array $variables = [])
    {
        $query = 'query purchaseOrder($companyId: Int!,$documentId: Int!,$options: PurchaseOrderOptionsSingle)
        {
            purchaseOrder(companyId: $companyId,documentId: $documentId,options: $options)
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

        return Curl::simple('documents/purchaseOrder', $query, $variables);
    }

    /**
     * Gets all purchase orders
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryPurchaseOrders(?array $variables = [])
    {
        $query = 'query purchaseOrders($companyId: Int!,$options: PurchaseOrderOptions)
        {
            purchaseOrders(companyId: $companyId,options: $options)
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

        return Curl::complex('documents/purchaseOrders', $query, $variables, 'purchaseOrders');
    }

    /**
     * Get document token and path for purchase orders
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryPurchaseOrderGetPDFToken(?array $variables = [])
    {
        $query = 'query purchaseOrderGetPDFToken($documentId: Int!)
        {
            purchaseOrderGetPDFToken(documentId: $documentId)
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

        return Curl::simple('documents/purchaseOrderGetPDFToken', $query, $variables);
    }

    /**
     * Creates a purchase order
     *
     * @param array|null $variables variables of the request
     *
     * @return mixed
     *
     * @throws Error
     */
    public static function mutationPurchaseOrderCreate(?array $variables = [])
    {
        $query = 'mutation purchaseOrderCreate($companyId: Int!,$data: PurchaseOrderInsert!,$options: PurchaseOrderMutateOptions)
        {
            purchaseOrderCreate(companyId: $companyId,data: $data,options: $options)
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

        return Curl::simple('documents/purchaseOrderCreate', $query, $variables);
    }

    /**
     * Update a purchase order
     *
     * @param array|null $variables
     *
     * @return mixed
     * @throws Error
     */
    public static function mutationPurchaseOrderUpdate(?array $variables = [])
    {
        $query = 'mutation purchaseOrderUpdate($companyId: Int!,$data: PurchaseOrderUpdate!)
        {
            purchaseOrderUpdate(companyId: $companyId,data: $data)
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

        return Curl::simple('documents/purchaseOrderUpdate', $query, $variables);
    }

    /**
     * Creates purchase order pdf
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function mutationPurchaseOrderGetPDF(?array $variables = [])
    {
        $query = 'mutation purchaseOrderGetPDF($companyId: Int!,$documentId: Int!)
        {
            purchaseOrderGetPDF(companyId: $companyId,documentId: $documentId)
        }';

        return Curl::simple('documents/purchaseOrderGetPDF', $query, $variables);
    }

    /**
     * Send purchased order by mail
     *
     * @param array|null $variables
     *
     * @return mixed
     * @throws Error
     */
    public static function mutationPurchaseOrderSendMail(?array $variables = [])
    {
        $query = 'mutation purchaseOrderSendMail($companyId: Int!,$documents: [Int]!,$mailData: MailData)
        {
            purchaseOrderSendMail(companyId: $companyId,documents: $documents,mailData: $mailData)
        }';

        return Curl::simple('documents/purchaseOrderSendMail', $query, $variables);
    }
}