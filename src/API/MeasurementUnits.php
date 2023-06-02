<?php

namespace MoloniES\API;

use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class MeasurementUnits
{
    /**
     * Gets all measurement units
     *
     * @param array $variables variables of the query
     *
     * @return array returns all measurement units
     * @throws APIExeption
     */
    public static function queryMeasurementUnits($variables = [])
    {
        $query = 'query measurementUnits($companyId: Int!,$options: MeasurementUnitOptions)
        {
            measurementUnits(companyId: $companyId,options: $options)
            {
                data
                {
                    measurementUnitId
                    name
                    abbreviation
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

        return Curl::complex('MeasurementUnits/measurementUnits', $query, $variables, 'measurementUnits');
    }

    /**
     * Create an measurement unit
     *
     * @param array $variables variables of the query
     *
     * @return array returns some data of the created measurement data
     * @throws APIExeption
     */
    public static function mutationMeasurementUnitCreate($variables = [])
    {
        $query = 'mutation measurementUnitCreate($companyId: Int!,$data: MeasurementUnitInsert!)
        {
            measurementUnitCreate(companyId: $companyId,data: $data)
            {
                data
                {
                    measurementUnitId
                }
                errors
                {
                    field
                    msg
                }
            }
        }';

        return Curl::simple('MeasurementUnits/measurementUnitCreate', $query, $variables);
    }
}
