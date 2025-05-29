<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class DocumentSets extends EndpointAbstract
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
        $query = self::loadQuery('documentSets');

        return Curl::complex('documentSets', $query, $variables);
    }
}
