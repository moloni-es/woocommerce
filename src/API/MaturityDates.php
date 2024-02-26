<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class MaturityDates extends EndpointAbstract
{
    /**
     * Get All Maturity Dates from Moloni ES
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryMaturityDates(?array $variables = []): array
    {
        $query = 'query maturityDates($companyId: Int!,$options: MaturityDateOptions){
            maturityDates(companyId: $companyId, options: $options) {
                errors{
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
                    maturityDateId
                    name
                    days
                    discount
                }
            }
        }';

        return Curl::complex('maturitydates/maturityDates', $query, $variables, 'maturityDates');
    }
}
