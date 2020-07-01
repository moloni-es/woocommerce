<?php

namespace MoloniES;

use MoloniES\API\Companies;

/**
 * Class Start
 * This is one of the main classes of the module
 * Every call should pass here before
 * This will render the login form or the company form or it will return a bol
 * This will also handle the tokens
 * @package Moloni
 */
class Start
{
    /** @var bool */
    private static $ajax = false;

    /**
     * Handles session, login and settings
     * @param bool $ajax
     * @return bool
     * @throws Error
     */
    public static function login($ajax = false)
    {
        global $wpdb;

        $action = isset($_REQUEST['action']) ? sanitize_text_field(trim($_REQUEST['action'])) : '';
        $developerId = isset($_POST['developer_id']) ? sanitize_text_field(trim($_POST['developer_id'])) : '';
        $clientSecret = isset($_POST['client_secret']) ? sanitize_text_field(trim($_POST['client_secret'])) : '';
        $code = isset($_GET['code']) ? sanitize_text_field(trim($_GET['code'])) : '';

        if ($ajax) {
            self::$ajax = true;
        }

        if (!empty($developerId) && !empty($clientSecret)) {
            Model::setClient($developerId,$clientSecret);
            $url = 'https://api.moloni.es/v1/auth/authorize?apiClientId=' . $developerId . '&redirectUri=' . urlencode(admin_url('admin.php?page=molonies'));
            wp_redirect($url);
            return true;
        }

        if(!empty($code)) {
            $tokensRow = Model::getTokensRow();
            $login = Curl::login($code,$tokensRow['client_id'],$tokensRow['client_secret']);
            if ($login && isset($login['accessToken']) && isset($login['refreshToken'])) {
                Model::setTokens($login['accessToken'],$login['refreshToken']);
            } else {
                return false;
            }
        }

        if ($action === 'logout') {
            Model::resetTokens();
        }

        if ($action === 'save') {
            add_settings_error('general', 'settings_updated', __('Changes saved.','moloni_es'), 'updated');
            $options = is_array($_POST['opt']) ? $_POST['opt'] : [];
            foreach ($options as $option => $value) {
                $option = sanitize_text_field($option);
                $value = sanitize_text_field($value);

                Model::setOption($option, $value);
            }
        }

        $tokensRow = Model::getTokensRow();

        if (!empty($tokensRow['main_token']) && !empty($tokensRow['refresh_token'])) {
            Model::refreshTokens();
            Model::defineValues();

            if (defined('MOLONIES_COMPANY_ID')) {
                Model::defineConfigs();
                return true;
            }

            if (isset($_GET['companyId'])) {
                $wpdb->update('moloni_es_api', ['company_id' => (int)$_GET['companyId']], ['id' => MOLONI_SESSION_ID]);
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

    /**
     * Shows a login form
     * @param bool|string $error Is used in include
     */
    public static function loginForm($error = false)
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
        try {
            $companiesIds = Companies::queryMe();
            foreach ($companiesIds['data']['me']['data']['userCompanies'] as $company) {
                $variables = [
                    'companyId' => $company['company']['companyId'],
                    'options' => ['defaultLanguageId' => 1]
                ];
                $query = Companies::queryCompany($variables);
                $companies[] = $query['data']['company']['data'];
            }

        } catch (Error $e) {
            $companies = [];
        }

        if (empty($companies)) {
            self::loginForm(__('You have no companies available in your account!','moloni_es'));
        }else if (!self::$ajax) {
            include(MOLONI_ES_TEMPLATE_DIR . 'CompanySelect.php');
        }
    }

}
