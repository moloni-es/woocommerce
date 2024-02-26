<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Taxes extends EndpointAbstract
{
    /**
     * Gets all the taxes of the company
     *
     * @param array|null $variables
     *
     * @return array returns an array with taxes information
     * @throws APIExeption
     */
    public static function queryTaxes(?array $variables = []): array
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
                    flags
                    {
                        flagId
                        name
                    }
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
     * @param array|null $variables
     *
     * @return array returns an array with taxes information
     * @throws APIExeption
     */
    public static function queryTax(?array $variables = []): array
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
                    flags
                    {
                        flagId
                        name
                    }
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
     * Creates a tax
     *
     * @param array|null $variables
     *
     * @return array returns data about the created tax
     * @throws APIExeption
     */
    public static function mutationTaxCreate(?array $variables = []): array
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
                    type
                    fiscalZone
                    flags
                    {
                        flagId
                        name
                    }
                    country
                    {
                        countryId
                    }
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
