<?php

namespace MoloniES;

use MoloniES\Services\Mails\AuthenticationExpired;

class Model
{
    /**
     * Return the row of moloni_api table with all the session details
     *
     * @global $wpdb
     */
    public static function getTokensRow()
    {
        global $wpdb;

        return $wpdb->get_row('SELECT * FROM ' . $wpdb->get_blog_prefix() . 'moloni_es_api ORDER BY id DESC', ARRAY_A);
    }

    /**
     * Adds client id and secret to the database
     *
     * @param string $clientId
     * @param string $clientSecret
     */
    public static function setClient(string $clientId, string $clientSecret)
    {
        global $wpdb;

        $wpdb->query('TRUNCATE ' . $wpdb->get_blog_prefix() . 'moloni_es_api');
        $wpdb->insert($wpdb->get_blog_prefix() . 'moloni_es_api', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret
        ]);
    }

    /**
     * Clear moloni_es_api and set new access and refresh token
     *
     * @param string $accessToken
     * @param string $refreshToken
     *
     * @global $wpdb
     */
    public static function setTokens(string $accessToken, string $refreshToken): void
    {
        global $wpdb;

        $wpdb->update($wpdb->get_blog_prefix() . 'moloni_es_api',
            ['main_token' => $accessToken, 'refresh_token' => $refreshToken],
            ['id' => 1]
        );
    }

    /**
     * Check if a setting exists on database and update it or create it
     *
     * @param string $option
     * @param string|null $value
     *
     * @return int
     */
    public static function setOption(string $option, ?string $value = ''): int
    {
        global $wpdb;

        $query = $wpdb->prepare('SELECT * FROM ' . $wpdb->get_blog_prefix() . 'moloni_es_api_config WHERE config = %s', $option);
        $setting = $wpdb->get_row($query, ARRAY_A);

        if (!empty($setting)) {
            $wpdb->update($wpdb->get_blog_prefix() . 'moloni_es_api_config',
                ['selected' => $value],
                ['config' => $option]
            );
        } else {
            $wpdb->insert($wpdb->get_blog_prefix() . 'moloni_es_api_config',
                ['selected' => $value, 'config' => $option]
            );
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
    public static function refreshTokens(int $retryNumber = 0): bool
    {
        global $wpdb;

        $tokensRow = self::getTokensRow() ?? [];

        $access_expire = $tokensRow['access_expire'] ?? false;
        $refresh_expire = $tokensRow['refresh_expire'] ?? false;

        if ($refresh_expire && $refresh_expire < time()) {
            $wpdb->query('TRUNCATE ' . $wpdb->get_blog_prefix() . 'moloni_es_api');

            return false;
        }

        if (!$access_expire || $access_expire < time()) {
            $results = Curl::refresh($tokensRow['client_id'], $tokensRow['client_secret'], $tokensRow['refresh_token']);

            if (isset($results['accessToken'], $results['refreshToken'])) {
                $wpdb->update($wpdb->get_blog_prefix() . 'moloni_es_api',
                    [
                        'main_token' => $results['accessToken'],
                        'refresh_token' => $results['refreshToken'],
                        'access_expire' => time() + 3000,
                        'refresh_expire' => time() + 864000
                    ],
                    ['id' => $tokensRow['id']]
                );
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

                    // Send e-mail notification if email is set
                    if (defined('ALERT_EMAIL') && !empty(ALERT_EMAIL)) {
                        new AuthenticationExpired(ALERT_EMAIL);
                    }

                    Storage::$LOGGER->error(sprintf(__('Reseting tokens after %s tries', 'moloni_es'), $retryNumber), [
                        'tag' => 'service:refreshtokens:error',
                    ]);

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

        Storage::$MOLONI_ES_SESSION_ID = $tokensRow['id'] ?? '';
        Storage::$MOLONI_ES_ACCESS_TOKEN = $tokensRow['main_token'] ?? '';

        if (!empty($tokensRow['company_id'])) {
            Storage::$MOLONI_ES_COMPANY_ID = (int)$tokensRow['company_id'];
        }
    }

    /**
     * Define company selected settings
     */
    public static function defineConfigs()
    {
        global $wpdb;

        $results = $wpdb->get_results('SELECT * FROM ' . $wpdb->get_blog_prefix() . 'moloni_es_api_config ORDER BY id DESC', ARRAY_A);

        foreach ($results as $result) {
            $setting = strtoupper($result['config']);

            if (!defined($setting)) {
                define($setting, $result['selected']);
            }
        }
    }

    /**
     * Get all available custom fields
     *
     * @return array
     */
    public static function getPossibleVatFields(): array
    {
        $customFields = [];
        $args = [
            'posts_per_page' => 50,
            'orderby' => 'date',
            'paginate' => false,
            'order' => 'DESC',
            'post_type' => 'shop_order'
        ];

        $orders = wc_get_orders($args);

        if (empty($orders)) {
            return $customFields;
        }

        foreach ($orders as $order) {
            $metas = $order->get_meta_data();

            if (empty($metas)) {
                continue;
            }

            foreach ($metas as $meta) {
                if (in_array($meta->key, $customFields)) {
                    continue;
                }

                $customFields[] = $meta->key;
            }
        }

        return $customFields;
    }

    /**
     * Resets database table
     */
    public static function resetTokens(): void
    {
        global $wpdb;

        Storage::$MOLONI_ES_ACCESS_TOKEN = '';
        Storage::$MOLONI_ES_COMPANY_ID = '';
        Storage::$MOLONI_ES_SESSION_ID = '';

        $wpdb->query('TRUNCATE ' . $wpdb->get_blog_prefix() . 'moloni_es_api');
    }
}
