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
        $query = self::loadQuery('taxes');

        return Curl::complex('taxes', $query, $variables);
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
        $query = self::loadQuery('tax');

        return Curl::simple('tax', $query, $variables);
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
        $query = self::loadMutation('taxCreate');

        return Curl::simple('taxCreate', $query, $variables);
    }
}
