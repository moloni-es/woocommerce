<?php

namespace MoloniES\API\Documents;

use MoloniES\API\Abstracts\EndpointAbstract;
use MoloniES\Curl;
use MoloniES\Exceptions\APIExeption;

class BillsOfLading extends EndpointAbstract
{
    /**
     * Creates a bill of lading
     *
     * @param array|null $variables variables of the request
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationBillsOfLadingCreate(?array $variables = [])
    {
        $query = self::loadMutation('billsOfLadingCreate');

        return Curl::simple('billsOfLadingCreate', $query, $variables);
    }

    /**
     * Creates a bill of lading
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationBillsOfLadingUpdate(?array $variables = [])
    {
        $query = self::loadMutation('billsOfLadingUpdate');

        return Curl::simple('billsOfLadingUpdate', $query, $variables);
    }

    /**
     * Get document token and path for bills of lading
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function queryBillsOfLadingGetPDFToken(?array $variables = [])
    {
        $query = self::loadQuery('billsOfLadingGetPDFToken');

        return Curl::simple('billsOfLadingGetPDFToken', $query, $variables);
    }

    /**
     * Creates bills of lading pdf
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationBillsOfLadingGetPDF(?array $variables = [])
    {
        $query = self::loadMutation('billsOfLadingGetPDF');

        return Curl::simple('billsOfLadingGetPDF', $query, $variables);
    }

    /**
     * Send bill of lading by email
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationBillsOfLadingSendMail(?array $variables = [])
    {
        $query = self::loadMutation('billsOfLadingSendMail');

        return Curl::simple('billsOfLadingSendMail', $query, $variables);
    }
}
