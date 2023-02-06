<?php

namespace MoloniES;

class Curl
{
    /**
     * Hold the request log
     *
     * @var array
     */
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
        'currencies/currencies',
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
    public static function simple($action, $query, $variables = [])
    {
        if (isset(self::$cache[$action]) && in_array($action, self::$simpleAllowedCachedMethods, false)) {
            return self::$cache[$action];
        }

        if (Storage::$MOLONI_ES_COMPANY_ID) {
            $variables['companyId'] = Storage::$MOLONI_ES_COMPANY_ID;
        }

        $data = json_encode(['query' => $query, 'variables' => $variables]);

        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . Storage::$MOLONI_ES_ACCESS_TOKEN
            ],
            'body' => $data,
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

        //errors sometimes come inside data/query(or mutation)
        $keyString = substr($action, strpos($action, '/') + strlen('/'));

        if (!empty($parsed['data'][$keyString]['data']) ||
            (!isset($parsed['errors']) && (empty($parsed['data'][$keyString]['errors'])))) {

            if (!isset(self::$cache[$action]) && in_array($action, self::$simpleAllowedCachedMethods, false)) {
                self::$cache[$action] = $parsed;
            }

            return $parsed;
        }

        throw new Error(__('Oops, an error was encountered...', 'moloni_es'), $log);
    }

    /**
     * Gets all values from a query and loops its pages of results
     *
     * @param $action
     * @param $query
     * @param $variables
     * @param $keyString
     *
     * @return array|bool
     *
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
     * Uploads a file
     *
     * @param $query
     * @param $variables
     * @param $file
     *
     * @return true
     */
    public static function uploadImage($query, $variables, $file)
    {
        $payload = '';
        $boundary = 'MOLONIRULES';
        $query = str_replace(["\n", "\r"], '', $query);

        $data = [
            'operations' => json_encode(['query' => $query, 'variables' => $variables]),
            'map' => '{ "0": ["variables.data.img"] }'
        ];

        $headers = [
            'Authorization' => 'Bearer ' . Storage::$MOLONI_ES_ACCESS_TOKEN,
            'Content-type' => 'multipart/form-data; boundary=' . $boundary,
        ];

        foreach ($data as $name => $value) {
            $payload .= '--' . $boundary;
            $payload .= "\r\n";
            $payload .= 'Content-Disposition: form-data; name="' . $name .
                '"' . "\r\n\r\n";
            $payload .= $value;
            $payload .= "\r\n";
        }

        $payload .= '--' . $boundary;
        $payload .= "\r\n";
        $payload .= 'Content-Disposition: form-data; name="0"; filename="' . basename($file) . '"' . "\r\n";
        $payload .= 'Content-Type: image/*' . "\r\n";
        $payload .= "\r\n";
        $payload .= file_get_contents($file);
        $payload .= "\r\n";
        $payload .= '--' . $boundary . '--';

        wp_remote_post(self::$url, [
                'headers' => $headers,
                'body' => $payload,
                'timeout' => 45
            ]
        );

        return true;
    }

    /**
     * Returns the last curl request made from the logs
     *
     * @return array
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
     * @throws Error
     */
    public static function login($code, $clientId, $clientSecret)
    {
        $url = self::$url . '/auth/grant';

        $postFields = 'grantType=' . 'authorization_code';
        $postFields .= '&apiClientId=' . $clientId;
        $postFields .= '&clientSecret=' . $clientSecret;
        $postFields .= '&code=' . $code;

        $response = wp_remote_post($url, ['body' => $postFields, 'timeout' => 45]);

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

        throw new Error(__('Oops, an error was encountered...', 'moloni_es'), $log);
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

        $response = wp_remote_post($url, ['body' => $postFields, 'timeout' => 45]);
        $raw = wp_remote_retrieve_body($response);

        $res_txt = json_decode($raw, true);

        if (!isset($res_txt['error'])) {
            return ($res_txt);
        }

        return false;
    }

}
