<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class DocumentSets
{
    /**
     * Get All Documents Set from Moloni ES
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryDocumentSets(?array $variables = []): array
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
}
