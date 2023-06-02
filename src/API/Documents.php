<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Documents extends EndpointAbstract
{
    /**
     * Gets documents info by id
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function queryDocument(?array $variables = [])
    {
        $query = 'query document($companyId: Int!,$documentId: Int!,$options: DocumentOptionsSingle)
        {
            document(companyId: $companyId,documentId: $documentId,options: $options)
            {
                data
                {
                    documentId
                    status
                    pdfExport
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
}
