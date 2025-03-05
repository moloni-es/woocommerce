<?php

namespace MoloniES;

use MoloniES\Exceptions\APIExeption;

class Curl
{
    /**
     * Hold the request log
     *
     * @var array
     */
    private static $logs = [];

    /**
     * API url
     *
     * @var
     */
    private static $url = 'https://api.moloni.es/v1';

    /**
     * Plugin user agent ID
     *
     * @var string
     */
    private static $userAgent = 'WordpressPlugin/2.0';

    /**
     * Makes a simple API post request
     *
     * @param $action
     * @param $query
     * @param array|null $variables
     *
     * @return mixed
     * @throws APIExeption
     */
    public static function simple($action, $query, ?array $variables = [])
    {
        if (Storage::$MOLONI_ES_COMPANY_ID) {
            $variables['companyId'] = Storage::$MOLONI_ES_COMPANY_ID;
        }

        $data = ['query' => $query];

        if (!empty($variables)) {
            $data['variables'] = $variables;
        }

        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . Storage::$MOLONI_ES_ACCESS_TOKEN,
                'user-agent' => self::$userAgent
            ],
            'body' => json_encode($data),
            'timeout' => 45
        ];

        $response = wp_remote_post(self::$url, $args);
        $raw = wp_remote_retrieve_body($response);
        $parsed = json_decode($raw, true);

        $log = [
            'url' => self::$url . '/' . $action,
            'sent' => $variables,
            'received' => $parsed
        ];

        self::$logs[] = $log;

        /** Errors sometimes come inside data/query(or mutation) */
        $keyString = substr($action, strpos($action, '/') + strlen('/'));

        if (empty($parsed['errors']) && empty($parsed['data'][$keyString]['errors'])) {
            return $parsed;
        }

        if (!empty($parsed['data'][$keyString]['data'])) {
            return $parsed;
        }

        throw new APIExeption(__('Oops, an error was encountered...', 'moloni_es'), $log);
    }

    /**
     * Uploads a file
     *
     * @param $headers
     * @param $payload
     */
    public static function simpleCustomPost($headers, $payload)
    {
        return wp_remote_post(
            self::$url,
            [
                'headers' => $headers,
                'body' => $payload,
                'timeout' => 45,
                'user-agent' => self::$userAgent
            ]
        );
    }

    /**
     * Gets all values from a query and loops its pages of results
     *
     * @param $action
     * @param $query
     * @param $variables
     * @param $keyString
     *
     * @return array
     *
     * @throws APIExeption
     */
    public static function complex($action, $query, $variables, $keyString)
    {
        /** To get all items we need to paginate */

        $page = 1;
        $array = [];

        do {
            $variables['options']['pagination']['qty'] = 50;
            $variables['options']['pagination']['page'] = $page;

            $fetch = self::simple($action, $query, $variables);

            $pagination = $fetch['data'][$keyString]['options']['pagination'] ?? ['count' => 0, 'qty' => 0, 'page' => 0];
            $results = $fetch['data'][$keyString]['data'] ?? [];

            $array = array_merge($array, $results);

            $page++;
        } while (($pagination['count'] > ($pagination['qty'] * $pagination['page'])) && $page <= 1000);

        return $array;
    }

    /**
     * Returns the last curl request made from the logs
     *
     * @return mixed
     */
    public static function getLog()
    {
        return end(self::$logs);
    }

    /**
     * Do a login request to the API
     *
     * @param $code
     * @param $clientId
     * @param $clientSecret
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function login($code, $clientId, $clientSecret)
    {
        $url = self::$url . '/auth/grant';

        $postFields = 'grantType=' . 'authorization_code';
        $postFields .= '&apiClientId=' . $clientId;
        $postFields .= '&clientSecret=' . $clientSecret;
        $postFields .= '&code=' . $code;

        $response = wp_remote_post(
            $url,
            ['body' => $postFields, 'timeout' => 45]
        );

        if (is_wp_error($response)) {
            throw new APIExeption($response->get_error_message(), [
                'code' => $response->get_error_code(),
                'data' => $response->get_error_data(),
                'message' => $response->get_error_message(),
            ]);
        }

        $raw = wp_remote_retrieve_body($response);

        $parsed = json_decode($raw, true);

        if (!isset($parsed['error'])) {
            return $parsed;
        }

        $log = [
            'url' => $url,
            'sent' => $postFields,
            'received' => $parsed
        ];

        throw new APIExeption(__('Invalid credentials', 'moloni_es'), $log);
    }

    /**
     * Refresh the session tokens
     *
     * @param $clientId
     * @param $clientSecret
     * @param $refreshToken
     *
     * @return bool|mixed
     */
    public static function refresh($clientId, $clientSecret, $refreshToken)
    {
        $url = self::$url . '/auth/grant';

        $postFields = 'grantType=' . 'refresh_token';
        $postFields .= '&apiClientId=' . $clientId;
        $postFields .= '&clientSecret=' . $clientSecret;
        $postFields .= '&refreshToken=' . $refreshToken;

        $response = wp_remote_post(
            $url,
            ['body' => $postFields, 'timeout' => 45, 'user-agent' => self::$userAgent]
        );
        $raw = wp_remote_retrieve_body($response);

        $res_txt = json_decode($raw, true);

        if (!isset($res_txt['error'])) {
            return ($res_txt);
        }

        return false;
    }

}
