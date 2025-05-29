<?php

namespace MoloniES\API;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class MeasurementUnits extends EndpointAbstract
{
    /**
     * Gets all measurement units
     *
     * @param array|null $variables
     *
     * @return array returns all measurement units
     *
     * @throws APIExeption
     */
    public static function queryMeasurementUnits(?array $variables = []): array
    {
        $query = self::loadQuery('measurementUnits');

        return Curl::complex('measurementUnits', $query, $variables);
    }

    /**
     * Create a measurement unit
     *
     * @param array|null $variables
     *
     * @return array returns some data of the created measurement data
     *
     * @throws APIExeption
     */
    public static function mutationMeasurementUnitCreate(?array $variables = []): array
    {
        $query = self::loadMutation('measurementUnitCreate');

        return Curl::simple('measurementUnitCreate', $query, $variables);
    }
}
