<?php

namespace MoloniES;

use MoloniES\API\Companies;
use MoloniES\Enums\Boolean;
use MoloniES\Exceptions\Error;
use MoloniES\Helpers\WebHooks;

/**
 * Class Start
 * This is one of the main classes of the module
 * Every call should pass here before
 * This will render the login form or the company form, or it will return a bool
 * This will also handle the tokens
 * @package Moloni
 */
class Start
{
    /** @var bool */
    private static $ajax = false;

    /**
     * Handles session, login and settings
     *
     * @param bool|null $ajax
     *
     * @return bool
     */
    public static function login(?bool $ajax = false): bool
    {
        self::$ajax = $ajax;

        $action = isset($_REQUEST['action']) ? sanitize_text_field(trim($_REQUEST['action'])) : '';
        $developerId = isset($_POST['developer_id']) ? sanitize_text_field(trim($_POST['developer_id'])) : '';
        $clientSecret = isset($_POST['client_secret']) ? sanitize_text_field(trim($_POST['client_secret'])) : '';
        $code = isset($_GET['code']) ? sanitize_text_field(trim($_GET['code'])) : '';

        if (!empty($developerId) && !empty($clientSecret)) {
            self::redirectToApi($developerId, $clientSecret);
            return true;
        }

        if (!empty($code)) {
            $loginValid = false;
            $errorMessage = '';
            $errorBag = [];

            try {
                $tokensRow = Model::getTokensRow();

                $login = Curl::login($code, $tokensRow['client_id'], $tokensRow['client_secret']);

                if ($login && isset($login['accessToken']) && isset($login['refreshToken'])) {
                    $loginValid = true;

                    Model::setTokens($login['accessToken'], $login['refreshToken']);
                }
            } catch (Error $e) {
                $errorMessage = $e->getMessage();
                $errorBag = $e->getRequest();
            }

            if (!$loginValid) {
                self::loginForm($errorMessage, $errorBag);
                return false;
            }
        }

        switch ($action) {
            case 'logout':
                self::logout();

                break;
            case 'saveSettings':
                self::saveSettings();

                break;
            case 'saveAutomations':
                self::saveAutomations();

                break;
        }

        $tokensRow = Model::getTokensRow();

        if (!empty($tokensRow['main_token']) && !empty($tokensRow['refresh_token'])) {
            Model::refreshTokens();
            Model::defineValues();

            if (Storage::$MOLONI_ES_COMPANY_ID) {
                Model::defineConfigs();

                return true;
            }

            if (isset($_GET['companyId'])) {
                global $wpdb;

                $wpdb->update($wpdb->get_blog_prefix() . 'moloni_es_api',
                    ['company_id' => (int)(sanitize_text_field($_GET['companyId']))],
                    ['id' => Storage::$MOLONI_ES_SESSION_ID]
                );

                Model::defineValues();
                Model::defineConfigs();

                return true;
            }

            self::companiesForm();

            return false;
        }

        self::loginForm();

        return false;
    }

    //          Form pages          //

    /**
     * Shows a login form
     *
     * @param bool|string $errorMessage Is used in include
     * @param bool|array $errorData Is used in include
     */
    public static function loginForm($errorMessage = false, $errorData = false)
    {
        if (!self::$ajax) {
            include(MOLONI_ES_TEMPLATE_DIR . 'LoginForm.php');
        }
    }

    /**
     * Draw all companies available to the user
     * Except the
     */
    public static function companiesForm()
    {
        if (self::$ajax) {
            return;
        }

        try {
            $companiesIds = Companies::queryMe();

            foreach ($companiesIds['data']['me']['data']['userCompanies'] as $company) {
                $variables = [
                    'companyId' => $company['company']['companyId'],
                    'options' => [
                        'defaultLanguageId' => 2
                    ]
                ];

                $query = Companies::queryCompany($variables)['data']['company']['data'];

                if (!$query['isConfirmed']) {
                    continue;
                }

                $companies[] = $query;
            }
        } catch (Error $e) {
            $companies = [];
        }

        include(MOLONI_ES_TEMPLATE_DIR . 'CompanySelect.php');
    }

    //          Auth          //

    /**
     * Redirects to API
     *
     * @return void
     */
    private static function redirectToApi(string $developerId, string $clientSecret)
    {
        Model::setClient($developerId, $clientSecret);

        $url = 'https://api.moloni.es/v1/auth/authorize?apiClientId=' . $developerId . '&redirectUri=' . urlencode(admin_url('admin.php?page=molonies'));

        wp_redirect($url);
    }

    /**
     * Removes plugin authentication
     *
     * @return void
     */
    private static function logout() {
        try {
            WebHooks::deleteHooks();
            Model::resetTokens();
        } catch (Error $e) {}
    }

    //          Settings/Automations          //

    /**
     * Save plugin settings
     *
     * @return void
     */
    private static function saveSettings() {
        add_settings_error('general', 'settings_updated', __('Changes saved.', 'moloni_es'), 'updated');

        $options = is_array($_POST['opt']) ? $_POST['opt'] : [];

        self::saveOptions($options);
    }

    /**
     * Save plugin automations
     *
     * @return void
     */
    private static function saveAutomations() {
        add_settings_error('general', 'automations_updated', __('Changes saved.', 'moloni_es'), 'updated');

        $options = is_array($_POST['opt']) ? $_POST['opt'] : [];

        /** Verifies checkboxes because they are not set if not checked */
        $syncOptions = [
            'sync_fields_description',
            'sync_fields_visibility',
            'sync_fields_stock',
            'sync_fields_name',
            'sync_fields_price',
            'sync_fields_categories',
            'sync_fields_ean',
            'sync_fields_image'
        ];

        /** for each sync opt check if it is set */
        foreach ($syncOptions as $option) {
            if (!isset($options[$option])) {
                $options[$option] = 0;
            }
        }

        self::saveOptions($options);

        try {
            WebHooks::deleteHooks();

            if (isset($options['hook_stock_update']) && (int)$options['hook_stock_update'] === Boolean::YES) {
                WebHooks::createHook('Product', 'stockChanged');
            }

            if (isset($options['hook_product_sync']) && (int)$options['hook_product_sync'] === Boolean::YES) {
                WebHooks::createHook('Product', 'create');
                WebHooks::createHook('Product', 'update');
            }
        } catch (Error $e) {}
    }

    /**
     * Save data in settings table
     *
     * @param array $options
     *
     * @return void
     */
    private static function saveOptions(array $options)
    {
        foreach ($options as $option => $value) {
            $option = sanitize_text_field($option);
            $value = sanitize_text_field($value);

            Model::setOption($option, $value);
        }
    }
}
