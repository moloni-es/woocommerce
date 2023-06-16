<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Companies extends EndpointAbstract
{
    /**
     * Gets all the companies that the logged-in user has access
     *
     * @return array return and array with all companies Ids
     *
     * @throws APIExeption
     */
    public static function queryMe(): array
    {
        $action = 'companies/me';

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

        if (empty(self::$cache[$action])) {
            self::$cache[$action] = Curl::simple($action, $query);
        }

        return self::$cache[$action];
    }

    /**
     * Gets the information of the companies that the logged-in user has access
     *
     * @param array|null $variables
     *
     * @return array returns an array with the companies information
     *
     * @throws APIExeption
     */
    public static function queryCompany(?array $variables = []): array
    {
        $query = 'query company($companyId: Int!,$options: CompanyOptionsSingle){ 
            company(companyId: $companyId,options: $options) { 
                data 
                { 
                    companyId
                    isConfirmed
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
                    subscription {
                        subscriptionId
                        plan 
                        {
                            planId
                            code
                        }
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

        return Curl::simple('companies/company', $query, $variables);
    }
}
