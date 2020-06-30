<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Error;

class MaturityDates
{
    /**
     * Get All Maturity Dates from Moloni ES
     *
     * @param $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws Error
     */
    public static function queryMaturityDates($variables)
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

        return Curl::complex('maturitydates/maturityDates', $query, $variables, 'maturityDates', false);
    }
}
