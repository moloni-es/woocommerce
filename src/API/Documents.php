<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Error;

class Documents
{
    /**
     * Gets documents info by id
     * @param $variables
     * @return mixed
     * @throws Error
     */
    public static function queryDocument($variables)
    {
        $query='query document($companyId: Int!,$documentId: Int!,$options: DocumentOptionsSingle)
        {
            document(companyId: $companyId,documentId: $documentId,options: $options)
            {
                data
                {
                    documentId
                    status
                    documentType
                    {
                        documentTypeId
                        apiCode
                        apiCodePlural
                        
                    }
                    company
                    {
                        companyId
                        slug
                    }
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('documents/document', $query, $variables);
    }

    /**
     * Get All Documents Set from Moloni ES
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryDocumentSets($variables)
    {
        $query = 'query documentSets($companyId: Int!,$options: DocumentSetOptions)
        {
            documentSets(companyId: $companyId, options: $options) 
            {
                errors
                {
                    field
                    msg
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
                data{
                    documentSetId
                    name
                    isDefault
                }
            }
        }';

        return Curl::complex('documents/documentSets', $query, $variables, 'documentSets');
    }

    /**
     * Gets invoice information
     *
     * @param $variables
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryInvoice($variables)
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
     * @param $variables
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryInvoices($variables)
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
     * Creates an invoice
     *
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function mutationInvoiceCreate($variables)
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
                }
            }
        }';

        return Curl::simple('documents/invoiceCreate', $query, $variables);
    }

    /**
     * Update an invoice
     *
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function mutationInvoiceUpdate($variables)
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
     * Gets receipt information
     *
     * @param $variables
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryReceipt($variables)
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
     * @param $variables
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryReceipts($variables)
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
     * Creates a receipt
     *
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function mutationReceiptCreate($variables)
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
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function mutationReceiptUpdate($variables)
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
     * Gets credit note information
     *
     * @param $variables
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryCreditNote($variables)
    {
        $query = 'query creditNote($companyId: Int!,$documentId: Int!,$options: CreditNoteOptionsSingle)
        {
            creditNote(companyId: $companyId,documentId: $documentId,options: $options)
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

        return Curl::simple('documents/creditNote', $query, $variables);
    }

    /**
     * Gets all credit notes
     *
     * @param $variables
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryCreditNotes($variables)
    {
        $query = 'query creditNotes($companyId: Int!,$options: CreditNoteOptions)
        {
            creditNotes(companyId: $companyId,options: $options)
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

        return Curl::complex('documents/creditNotes', $query, $variables, 'creditNotes');
    }

    /**
     * Creates a credit note
     *
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function mutationCreditNoteCreate($variables)
    {
        $query = 'mutation creditNoteCreate($companyId: Int!,$data: CreditNoteInsert!,$options:CreditNoteMutateOptions)
        {
            creditNoteCreate(companyId: $companyId,data: $data,options: $options)
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

        return Curl::simple('documents/creditNoteCreate', $query, $variables);
    }

    /**
     * Gets simplified invoice information
     *
     * @param $variables
     *
     * @return array Api data
     * @throws Error
     */
    public static function querySimplifiedInvoice($variables)
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
     * @param $variables
     *
     * @return array Api data
     * @throws Error
     */
    public static function querySimplifiedInvoices($variables)
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
     * Creates a simplified invoice
     *
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function mutationSimplifiedInvoiceCreate($variables)
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
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function mutationSimplifiedInvoiceUpdate($variables)
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
     * Creates a purchase order
     *
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryPurchaseOrder($variables)
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
     * @param $variables
     *
     * @return array Api data
     * @throws Error
     */
    public static function queryPurchaseOrders($variables)
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
     * Creates a purchase order
     *
     * @param array $variables variables of the request
     *
     * @return mixed Api data
     * @throws Error
     */
    public static function mutationPurchaseOrderCreate($variables)
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
     * @param $variables
     * @return mixed
     * @throws Error
     */
    public static function mutationPurchaseOrderUpdate($variables)
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
     * Creates a pro forma invoice
     *
     * @param array $variables variables of the request
     *
     * @return void Api data
     * @throws Error
     */
    public static function queryProFormaInvoice($variables)
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
     * @param $variables
     *
     * @return array|bool Api data
     * @throws Error
     */
    public static function queryProFormaInvoices($variables)
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
     * Creates a pro forma invoice
     *
     * @param array $variables variables of the request
     *
     * @return mixed Api data
     * @throws Error
     */
    public static function mutationProFormaInvoiceCreate($variables)
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
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function mutationProFormaInvoiceUpdate($variables)
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
     * Creates an bill of lading
     *
     * @param array $variables variables of the request
     *
     * @return array Api data
     * @throws Error
     */
    public static function mutationBillsOfLadingCreate($variables)
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
                }
            }
        }';

        return Curl::simple('documents/billsOfLadingCreate', $query, $variables);
    }

    /**
     * Creates an bill of lading
     * @param $variables
     * @return mixed
     * @throws Error
     */
    public static function mutationBillsOfLadingUpdate($variables)
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
     * Get document token and path for simplified invoices
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function querySimplifiedInvoiceGetPDFToken($variables)
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
     * Get document token and path for invoices
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryInvoiceGetPDFToken($variables)
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
     * Get document token and path for receipts
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryReceiptGetPDFToken($variables)
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
     * Get document token and path for credit notes
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryCreditNoteGetPDFToken($variables)
    {
        $query = 'query creditNoteGetPDFToken($documentId: Int!)
        {
            creditNoteGetPDFToken(documentId: $documentId)
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

        return Curl::simple('documents/creditNoteGetPDFToken', $query, $variables);
    }

    /**
     * Get document token and path for pro forma invoices
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryProFormaInvoiceGetPDFToken($variables)
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
     * Get document token and path for purchase orders
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryPurchaseOrderGetPDFToken($variables)
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
     * Get document token and path for bills of lading
     *
     * @param $variables
     * @return mixed
     * @throws Error
     */
    public static function queryBillsOfLadingGetPDFToken($variables)
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
     * Creates simplified invoice pdf
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function mutationSimplifiedInvoiceGetPDF($variables)
    {
        $query = 'mutation simplifiedInvoiceGetPDF($companyId: Int!,$documentId: Int!)
        {
            simplifiedInvoiceGetPDF(companyId: $companyId,documentId: $documentId)
        }';

        return Curl::simple('documents/simplifiedInvoiceGetPDF', $query, $variables);
    }

    /**
     * Creates invoice pdf
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function mutationInvoiceGetPDF($variables)
    {
        $query = 'mutation invoiceGetPDF($companyId: Int!,$documentId: Int!)
        {
            invoiceGetPDF(companyId: $companyId,documentId: $documentId)
        }';

        return Curl::simple('documents/invoiceGetPDF', $query, $variables);
    }

    /**
     * Creates receipt pdf
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function mutationReceiptGetPDF($variables)
    {
        $query = 'mutation receiptGetPDF($companyId: Int!,$documentId: Int!)
        {
            receiptGetPDF(companyId: $companyId,documentId: $documentId)
        }';

        return Curl::simple('documents/receiptGetPDF', $query, $variables);
    }

    /**
     * Creates credit notes pdf
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function mutationCreditNoteGetPDF($variables)
    {
        $query = 'mutation creditNoteGetPDF($companyId: Int!,$documentId: Int!)
        {
            creditNoteGetPDF(companyId: $companyId,documentId: $documentId)
        }';

        return Curl::simple('documents/creditNoteGetPDF', $query, $variables);
    }

    /**
     * Creates pro forma invocie pdf
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function mutationProFormaInvoiceGetPDF($variables)
    {
        $query = 'mutation proFormaInvoiceGetPDF($companyId: Int!,$documentId: Int!)
        {
            proFormaInvoiceGetPDF(companyId: $companyId,documentId: $documentId)
        }';

        return Curl::simple('documents/proFormaInvoiceGetPDF', $query, $variables);
    }

    /**
     * Creates purchase order pdf
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function mutationPurchaseOrderGetPDF($variables)
    {
        $query = 'mutation purchaseOrderGetPDF($companyId: Int!,$documentId: Int!)
        {
            purchaseOrderGetPDF(companyId: $companyId,documentId: $documentId)
        }';

        return Curl::simple('documents/purchaseOrderGetPDF', $query, $variables);
    }

    /**
     * Creates bills of lading pdf
     * @param $variables
     * @return mixed
     * @throws Error
     */
    public static function mutationBillsOfLadingGetPDF($variables)
    {
        $query = 'mutation billsOfLadingGetPDF($companyId: Int!,$documentId: Int!)
        {
            billsOfLadingGetPDF(companyId: $companyId,documentId: $companyId)
        }';

        return Curl::simple('documents/billsOfLadingGetPDF', $query, $variables);
    }

    /**
     * Send invoice by mail
     * @param $variables
     * @return mixed
     * @throws Error
     */
    public static function mutationInvoiceSendMail($variables)
    {
        $query = 'mutation invoiceSendMail($companyId: Int!,$documents: [Int]!,$mailData: MailData)
        {
            invoiceSendMail(companyId: $companyId,documents: $documents,mailData: $mailData)
        }';

        return Curl::simple('documents/invoiceSendMail', $query, $variables);
    }

    /**
     * Send pro forma invoice by mail
     * @param $variables
     * @return mixed
     * @throws Error
     */
    public static function mutationProFormaInvoiceSendMail($variables)
    {
        $query = 'mutation proFormaInvoiceSendMail($companyId: Int!,$documents: [Int]!,$mailData: MailData)
        {
            proFormaInvoiceSendMail(companyId: $companyId,documents: $documents,mailData: $mailData)
        }';

        return Curl::simple('documents/proFormaInvoiceSendMail', $query, $variables);
    }

    /**
     * Send purchased order by mail
     * @param $variables
     * @return mixed
     * @throws Error
     */
    public static function mutationPurchaseOrderSendMail($variables)
    {
        $query = 'mutation purchaseOrderSendMail($companyId: Int!,$documents: [Int]!,$mailData: MailData)
        {
            purchaseOrderSendMail(companyId: $companyId,documents: $documents,mailData: $mailData)
        }';

        return Curl::simple('documents/purchaseOrderSendMail', $query, $variables);
    }

    /**
     *Send receipt by mail
     * @param $variables
     * @return mixed
     * @throws Error
     */
    public static function mutationReceiptSendMail($variables)
    {
        $query = 'mutation receiptSendMail($companyId: Int!,$documents: [Int]!,$mailData: MailData)
        {
            receiptSendMail(companyId: $companyId,documents: $documents,mailData: $mailData)
        }';

        return Curl::simple('documents/receiptSendMail', $query, $variables);
    }

    /**
     * Send simplified invoice by mail
     * @param $variables
     * @return mixed
     * @throws Error
     */
    public static function mutationSimplifiedInvoiceSendMail($variables)
    {
        $query = 'mutation simplifiedInvoiceSendMail($companyId: Int!,$documents: [Int]!,$mailData: MailData)
        {
            simplifiedInvoiceSendMail(companyId: $companyId,documents: $documents,mailData: $mailData)
        }';

        return Curl::simple('documents/simplifiedInvoiceSendMail', $query, $variables);
    }

    /**
     * Send bill of lading by email
     * @param $variables
     * @return mixed
     * @throws Error
     */
    public static function mutationBillsOfLadingSendMail($variables)
    {
        $query = 'mutation billsOfLadingSendMail($companyId: Int!,$documents: [Int]!,$mailData: MailData)
        {
            billsOfLadingSendMail(companyId: companyId,documents: $documents,mailData: $mailData)
        }';

        return Curl::simple('documents/billsOfLadingSendMail', $query, $variables);
    }
}
