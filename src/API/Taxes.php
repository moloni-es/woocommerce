<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Error;

class Taxes
{
    /**
     * Gets all the taxes of the company
     *
     * @param array $variables variables of the query
     *
     * @return array returns an array with taxes information
     * @throws Error
     */
    public static function queryTaxes($variables)
    {
        $query = 'query taxes($companyId: Int!,$options: TaxOptions)
        {
            taxes(companyId: $companyId,options: $options)
            {
                data
                {
                    taxId
                    name
                    value
                    type
                    fiscalZone
                    country
                    {
                        countryId
                    }
                    fiscalZoneFinanceType
                    isDefault
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

        return Curl::complex('taxes/taxes', $query, $variables, 'taxes');
    }

    /**
     * Gets tax info
     *
     * @param array $variables variables of the query
     *
     * @return array returns an array with taxes information
     * @throws Error
     */
    public static function queryTax($variables)
    {
        $query = 'query tax($companyId: Int!,$taxId: Int!)
        {
            tax(companyId: $companyId,taxId: $taxId)
            {
                data
                {
                    taxId
                    name
                    value
                    type
                    fiscalZone
                    country
                    {
                        countryId
                    }
                    fiscalZoneFinanceType
                    fiscalZoneFinanceTypeMode
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('taxes/tax', $query, $variables);
    }

    /**
     * Creates an tax
     *
     * @param array $variables variables of the query
     *
     * @return array returns data about the created tax
     * @throws Error
     */
    public static function mutationTaxCreate($variables)
    {
        $query = 'mutation taxCreate($companyId: Int!,$data: TaxInsert!)
        {
            taxCreate(companyId: $companyId,data: $data)
            {
                data
                {
                    taxId
                    name
                    value
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('taxes/taxCreate', $query, $variables);
    }
}
