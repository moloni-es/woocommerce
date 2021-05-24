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
     * API url
     *
     * @var
     */
    private static $url = 'https://api.moloni.es/v1';

    /**
     * Makes a simple API post request
     *
     * @param $action
     * @param $query
     * @param $variables
     *
     * @return mixed
     * @throws Error
     */
    public static function simple($action, $query, $variables)
    {
        if (isset(self::$cache[$action]) && in_array($action, self::$simpleAllowedCachedMethods, false)) {
            return self::$cache[$action];
        }

        $data = json_encode(['query' => $query, 'variables' => $variables]);

        $args = [
            'headers' => [
                'Content-Type' => 'application/json' ,
                'Authorization' => 'Bearer '. MOLONI_ACCESS_TOKEN
            ],
            'body' => $data
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
     * Makes a simple API post request
     * @param $action
     * @param $query
     * @param $variables
     * @param $map
     * @param $values
     *
     * @return mixed
     * @throws Error
     */
    public static function simpleMultipart($action, $query, $variables, $map, $values)
    {
        $query = str_replace(array("\n", "\r"), '', $query);

        $post = [
            'operations' => json_encode(['query' => $query, 'variables' => $variables]),
            'map' => $map
        ];

        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $post[$key] = (new \CURLFile($value));
            }
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => self::$url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . MOLONI_ACCESS_TOKEN
            ],
        ]);

        $response = curl_exec($curl);
        $parsed = json_decode($response, true);

        curl_close($curl);

        $log = [
            'url' => self::$url . '/' . $action,
            'sent' => $variables,
            'received' => $parsed
        ];

        self::$logs[] = $log;

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
    public static function complex($action, $query, $variables, $keyString)
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

            $result = self::simple($action, $query, $variables);

            $pagination = $result['data'][$keyString]['options']['pagination'];
            $array = array_merge($array, $result['data'][$keyString]['data']);
            $page++;
        } while (($pagination['count'] > ($pagination['qty'] * $pagination['page'])) && $page <= 1000);

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
