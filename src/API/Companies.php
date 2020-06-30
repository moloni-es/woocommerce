<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Error;

class Companies
{
    /**
     * Gets all the companies that the logged in user has access
     *
     * @return array return and array with all companies Ids
     * @throws Error
     */
    public static function queryMe()
    {
        $query = 'query{
            me { 
                data { 
                    userCompanies { 
                        company { 
                            companyId 
                        } 
                    }
                } 
                errors 
                { field 
                msg 
                }
            }
        }';

        return Curl::simple('companies/me', $query, [], false);
    }

    /**
     * Gets the information of the companies that the logged in user has access
     *
     * @param array $variables variables of the query
     *
     * @return array returns an array with the companies information
     * @throws Error
     */
    public static function queryCompany($variables)
    {
        $query = 'query company($companyId: Int!,$options: CompanyOptionsSingle){ 
            company(companyId: $companyId,options: $options) { 
                data 
                { 
                    companyId
                    name
                    email
                    address
                    city
                    zipCode
                    slug
                    vat
                    fiscalZone
                    {
                        fiscalZone
                        fiscalZoneFinanceTypes
                        {
                            id
                            name
                        }
                    }
                    country
                    {
                        countryId
                    }
                    currency
                    {
                        currencyId
                        iso4217
                    }
                }
                options
                {
                    defaultLanguageId
                }
                errors 
                { 
                    field 
                    msg 
                }
            }
        }';

        return Curl::simple('companies/company', $query, $variables, false);
    }
}
