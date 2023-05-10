<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Exceptions\Error;

class FiscalZone
{
    /**
     * Get settings for a fiscal zone
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryFiscalZoneTaxSettings($variables = [])
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