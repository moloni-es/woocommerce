<?php

namespace MoloniES;

class Curl
{

    ///** @var string Moloni Client Not-so-secret used for WooCommerce */
    //private static $moloniClient = 'devapi';

    ///** @var string Moloni Not-so-secret key used for WooCommerce */
    //private static $moloniSecret = '53937d4a8c5889e58fe7f105369d9519a713bf43';

    /** @var array Hold the request log */
    private static $logs = [];

    /**
     * Hold a list of methods that can be cached
     * @var array
     */
    private static $simpleAllowedCachedMethods = [
        'companies/me'
    ];

    /**
     * Hold a list of methods that can be cached
     * @var array
     */
    private static $complexAllowedCachedMethods = [
        'countries/countries',
        'currencies/currencies',
        'taxes/taxes',
        'paymentmethods/paymentMethods'
    ];

    /**
     * Save a request cache
     * @var array
     */
    private static $cache = [];

    /**
     * Makes a simple API post request
     * @param $action
     * @param $query
     * @param $variables
     * @param bool $debug
     * @param string $keyString
     * @return mixed
     * @throws Error
     */
    public static function simple($action, $query, $variables, $debug = false)
    {
        if (isset(self::$cache[$action]) && in_array($action, self::$simpleAllowedCachedMethods, false)) {
            return self::$cache[$action];
        }

        $url = 'https://api.moloni.es/v1';
        $data = json_encode(['query' => $query, 'variables' => $variables]);
        $args = [
            'headers' => [
                'Content-Type' => 'application/json' ,
                'Authorization' => 'Bearer '. MOLONI_ACCESS_TOKEN
            ],
            'body' => $data
        ];

        $response = wp_remote_post($url, $args);
        $raw = wp_remote_retrieve_body($response);
        $parsed = json_decode($raw, true);

        $log = [
            'url' => $url . '/' . $action,
            'sent' => $variables,
            'received' => $parsed
        ];

        self::$logs[] = $log;

        if ($debug) {
            echo '<pre>';
            print_r($log);
            echo '</pre>';
        }

        //errors sometimes come inside data/query(or mutation)
        $keyString = substr($action, strpos($action,'/') + strlen('/'));

        if (!isset($parsed['errors']) && (!isset($parsed['data'][$keyString]['errors']) || empty($parsed['data'][$keyString]['errors']))) {

            if (!isset(self::$cache[$action]) && in_array($action, self::$simpleAllowedCachedMethods, false)) {
                self::$cache[$action] = $parsed;
            }

            return $parsed;
        }

        throw new Error(__('Oops, an error was encountered...','moloni_es'), $log);
    }

    /**
     * Gets all values from a query and loops its pages of results
     * @param $action
     * @param $query
     * @param $variables
     * @param $keyString
     * @param bool $debug
     * @return array|bool
     * @throws Error
     */
    public static function complex($action, $query, $variables, $keyString, $debug = false)
    {
        if (isset(self::$cache[$action]) && in_array($action, self::$complexAllowedCachedMethods, false)) {
            return self::$cache[$action];
        }

        //to get all items we need to paginate
        $page = 1;
        $array = [];
        do {
            $variables['options']['pagination']['qty'] = 50;
            $variables['options']['pagination']['page'] = $page;

            $result = self::simple($action, $query, $variables, $debug );

            $pagination = $result['data'][$keyString]['options']['pagination'];
            $array = array_merge($array, $result['data'][$keyString]['data']);
            $page++;
        } while (($pagination['count'] > ($pagination['qty'] * $pagination['page'])) || $page >= 1000);

        if (!isset(self::$cache[$action]) && in_array($action, self::$complexAllowedCachedMethods, false)) {
            self::$cache[$action] = $array;
        }

        return $array;
    }

    /**
     * Returns the last curl request made from the logs
     * @return array
     */
    public static function getLog()
    {
        return end(self::$logs);
    }

    /**
     * Do a login request to the API
     * @param $code
     * @param $clientId
     * @param $clientSecret
     * @return mixed
     * @throws Error
     */
    public static function login($code,$clientId,$clientSecret)
    {
        $url = 'https://api.moloni.es/v1/auth/grant';

        $postFields = 'grantType=' . 'authorization_code';
        $postFields .= '&apiClientId=' . $clientId;
        $postFields .= '&clientSecret=' . $clientSecret;
        $postFields .= '&code=' . $code;

        $response = wp_remote_post($url, ['body' => $postFields]);

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

        throw new Error(__('Oops, an error was encountered...','moloni_es'), $log);
    }

    /**
     * Refresh the session tokens
     * @param $clientId
     * @param $clientSecret
     * @param $refreshToken
     * @return bool|mixed
     */
    public static function refresh($clientId,$clientSecret,$refreshToken)
    {
        $url = 'https://api.moloni.es/v1/auth/grant';

        $postFields = 'grantType=' . 'refresh_token';
        $postFields .= '&apiClientId=' . $clientId;
        $postFields .= '&clientSecret=' . $clientSecret;
        $postFields .= '&refreshToken=' . $refreshToken;

        $response = wp_remote_post($url, ['body' => $postFields]);
        $raw = wp_remote_retrieve_body($response);

        $res_txt = json_decode($raw, true);
        if (!isset($res_txt['error'])) {
            return ($res_txt);
        }

        return false;
    }

}
