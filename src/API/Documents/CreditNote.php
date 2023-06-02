<?php

namespace MoloniES\API\Documents;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class CreditNote extends EndpointAbstract
{
    /**
     * Gets credit note information
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryCreditNote(?array $variables = []): array
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
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryCreditNotes(?array $variables = []): array
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
     * Get document token and path for credit notes
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryCreditNoteGetPDFToken(?array $variables = []): array
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
     * Creates a credit note
     *
     * @param array|null $variables variables of the request
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function mutationCreditNoteCreate(?array $variables = []): array
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
     * Creates credit notes pdf
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function mutationCreditNoteGetPDF(?array $variables = []): array
    {
        $query = 'mutation creditNoteGetPDF($companyId: Int!,$documentId: Int!)
        {
            creditNoteGetPDF(companyId: $companyId,documentId: $documentId)
        }';

        return Curl::simple('documents/creditNoteGetPDF', $query, $variables);
    }
}
