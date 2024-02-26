<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class FiscalZone extends EndpointAbstract
{
    /**
     * Get settings for a fiscal zone
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws APIExeption
     */
    public static function queryFiscalZoneTaxSettings(?array $variables = []): array
    {
        $query = 'query fiscalZoneTaxSettings($companyId: Int!,$fiscalZone: String!)
        {
            fiscalZoneTaxSettings(companyId: $companyId,fiscalZone: $fiscalZone)
            {
                fiscalZone
                fiscalZoneModes
                {
                    typeId
                    name
                    visible
                    type
                    values
                    {
                        code
                        name
                    }
                }
                fiscalZoneFinanceTypes
                {
                    id
                    name
                    code
                    isVAT
                }
            }
        }';

        return Curl::simple('fiscalZone/fiscalZoneTaxSettings', $query, $variables);
    }
}
