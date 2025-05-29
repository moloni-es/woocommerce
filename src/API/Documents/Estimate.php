<?php

namespace MoloniES\API\Documents;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class Estimate extends EndpointAbstract
{
    /**
     * Gets estimate information
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function queryEstimate(?array $variables = []): array
    {
        $query = self::loadQuery('estimate');

        return Curl::simple('estimate', $query, $variables);
    }

    /**
     * Get document token and path for estimates
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function queryEstimateGetPDFToken(?array $variables = []): array
    {
        $query = self::loadQuery('estimateGetPDFToken');

        return Curl::simple('estimateGetPDFToken', $query, $variables);
    }

    /**
     * Creates an estimate
     *
     * @param array|null $variables variables of the request
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationEstimateCreate(?array $variables = []): array
    {
        $query = self::loadMutation('estimateCreate');

        return Curl::simple('estimateCreate', $query, $variables);
    }

    /**
     * Update an estimate
     *
     * @param array|null $variables variables of the request
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationEstimateUpdate(?array $variables = []): array
    {
        $query = self::loadMutation('estimateUpdate');

        return Curl::simple('estimateUpdate', $query, $variables);
    }

    /**
     * Creates estimate pdf
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationEstimateGetPDF(?array $variables = []): array
    {
        $query = self::loadMutation('estimateGetPDF');

        return Curl::simple('estimateGetPDF', $query, $variables);
    }

    /**
     * Send estimate by mail
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationEstimateSendMail(?array $variables = [])
    {
        $query = self::loadMutation('estimateSendMail');

        return Curl::simple('estimateSendMail', $query, $variables);
    }
}
