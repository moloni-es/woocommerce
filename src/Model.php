<?php

/**
 * @noinspection SqlNoDataSourceInspection
 * @noinspection SqlResolve
 */

namespace MoloniES;

class Model
{
    /**
     * Return the row of moloni_api table with all the session details
     * @return array|false
     * @global $wpdb
     */
    public static function getTokensRow()
    {
        global $wpdb;

        return $wpdb->get_row('SELECT * FROM moloni_es_api ORDER BY id DESC', ARRAY_A);
    }

    /**
     * Adds client id and secret to the database
     *
     * @param int $clientId
     * @param string $clientSecret
     *
     * @return true
     */
    public static function setClient($clientId, $clientSecret)
    {
        global $wpdb;

        $wpdb->query('TRUNCATE moloni_es_api');
        $wpdb->insert('moloni_es_api', ['client_id' => $clientId, 'client_secret' => $clientSecret]);

        return true;
    }

    /**
     * Clear moloni_es_api and set new access and refresh token
     * @param string $accessToken
     * @param string $refreshToken
     * @return array|false
     * @global $wpdb
     */
    public static function setTokens($accessToken, $refreshToken)
    {
        global $wpdb;

        $wpdb->update('moloni_es_api', ['main_token' => $accessToken, 'refresh_token' => $refreshToken], ['id' => 1]);

        return true;
    }

    /**
     * Check if a setting exists on database and update it or create it
     * @param string $option
     * @param string $value
     * @return int
     * @global $wpdb
     */
    public static function setOption($option, $value)
    {
        global $wpdb;
        $setting = $wpdb->get_row($wpdb->prepare('SELECT * FROM moloni_es_api_config WHERE config = %s', $option), ARRAY_A);

        if (!empty($setting)) {
            $wpdb->update('moloni_es_api_config', ['selected' => $value], ['config' => $option]);
        } else {
            $wpdb->insert('moloni_es_api_config', ['selected' => $value, 'config' => $option]);
        }

        return $wpdb->insert_id;
    }

    /**
     * Checks if tokens need to be refreshed and refreshes them
     * If it fails, log user out
     *
     * @param int $retryNumber Number of current retries
     *
     * @return bool
     * @global $wpdb
     */
    public static function refreshTokens($retryNumber = 0)
    {
        global $wpdb;
        $tokensRow = self::getTokensRow();

        $access_expire = false;
        $refresh_expire = false;

        if (!isset($tokensRow['access_expire']) && !isset($tokensRow['refresh_expire'])) {
            $wpdb->query('ALTER TABLE moloni_es_api ADD access_expire varchar(250)');
            $wpdb->query('ALTER TABLE moloni_es_api ADD refresh_expire varchar(250)');
        } else {
            $access_expire = $tokensRow['access_expire'];
            $refresh_expire = $tokensRow['refresh_expire'];
        }

        if ($refresh_expire !== false && $refresh_expire < time()) {
            $wpdb->query('TRUNCATE moloni_es_api');

            return false;
        }

        if (!$access_expire || $access_expire < time()) {
            $results = Curl::refresh($tokensRow['client_id'], $tokensRow['client_secret'], $tokensRow['refresh_token']);

            if (isset($results['accessToken'], $results['refreshToken'])) {
                $wpdb->update('moloni_es_api', [
                    'main_token' => $results['accessToken'],
                    'refresh_token' => $results['refreshToken'],
                    'access_expire' => time() + 3000,
                    'refresh_expire' => time() + 864000
                ], [
                    'id' => $tokensRow['id']
                ]);
            } else {
                $recheckTokens = self::getTokensRow();

                if (empty($recheckTokens) ||
                    empty($recheckTokens['main_token']) ||
                    empty($recheckTokens['refresh_token']) ||
                    $recheckTokens['main_token'] === $tokensRow['main_token'] ||
                    $recheckTokens['refresh_token'] === $tokensRow['refresh_token']) {
                    if ($retryNumber <= 3) {
                        $retryNumber++;

                        return self::refreshTokens($retryNumber);
                    }

                    Log::write(__('Reseting tokens after 3 tries','moloni_es'));

                    self::resetTokens();

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Define constants from database
     */
    public static function defineValues()
    {
        $tokensRow = self::getTokensRow();

        define('MOLONI_SESSION_ID', $tokensRow['id']);
        define('MOLONI_ACCESS_TOKEN', $tokensRow['main_token']);

        if (!empty($tokensRow['company_id'])) {
            define('MOLONIES_COMPANY_ID', $tokensRow['company_id']);
        }
    }

    /**
     * Define company selected settings
     */
    public static function defineConfigs()
    {
        global $wpdb;
        $results = $wpdb->get_results('SELECT * FROM moloni_es_api_config ORDER BY id DESC', ARRAY_A);
        foreach ($results as $result) {
            $setting = strtoupper($result['config']);

            if (!defined($setting)) {
                define($setting, $result['selected']);
            }
        }
    }

    /**
     * Get all available custom fields
     * @return array
     */
    public static function getCustomFields()
    {
        global $wpdb;

        $results = $wpdb->get_results(
            'SELECT DISTINCT meta_key FROM ' . $wpdb->prefix . 'postmeta ORDER BY `' . $wpdb->prefix . 'postmeta`.`meta_key`',
            ARRAY_A
        );

        $customFields = [];
        if ($results && is_array($results)) {
            foreach ($results as $result) {
                $customFields[] = $result;
            }
        }
        return $customFields;
    }

    /**
     * Resets database table
     *
     * @return true
     */
    public static function resetTokens()
    {
        global $wpdb;

        $wpdb->query('TRUNCATE moloni_es_api');

        return true;
    }

    /**
     * Creates hash from company id
     * @return string
     */
    public static function createHash()
    {
        return hash('md5', (int)MOLONIES_COMPANY_ID);
    }

    /**
     * Checks if hash with company id hash
     * @param $hash
     * @return bool
     */
    public static function checkHash($hash)
    {
        return hash('md5', (int)MOLONIES_COMPANY_ID) === $hash;
    }

}
