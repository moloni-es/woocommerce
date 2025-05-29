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
        $query = self::loadQuery('creditNote');

        return Curl::simple('creditNote', $query, $variables);
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
        $query = self::loadQuery('creditNotes');

        return Curl::complex('creditNotes', $query, $variables);
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
        $query = self::loadQuery('creditNoteGetPDFToken');

        return Curl::simple('creditNoteGetPDFToken', $query, $variables);
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
        $query = self::loadMutation('creditNoteCreate');

        return Curl::simple('creditNoteCreate', $query, $variables);
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
        $query = self::loadMutation('creditNoteGetPDF');

        return Curl::simple('creditNoteGetPDF', $query, $variables);
    }
}
