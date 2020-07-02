<?php

namespace MoloniES\Activators;

class Install
{
    /**
     * Run the installation process
     * Install API Connection table
     * Install Settings table
     * Start sync crons
     */
    public static function run()
    {
        if (!function_exists('curl_version')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(esc_html__('cURL library is required for using Moloni Plugin.', 'moloni_es'));
        }

        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(esc_html__('Requires WooCommerce 3.0.0 or above.', 'moloni_es'));
        }

        self::createTables();
        self::insertSettings();
    }

    /**
     * Create API connection table
     */
    private static function createTables()
    {
        global $wpdb;
        $wpdb->query(
            'CREATE TABLE IF NOT EXISTS `moloni_es_api`( 
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
                main_token VARCHAR(100), 
                refresh_token VARCHAR(100), 
                client_id VARCHAR(100), 
                client_secret VARCHAR(100), 
                company_id INT,
                dated TIMESTAMP default CURRENT_TIMESTAMP
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;'
        );

        $wpdb->query(
            'CREATE TABLE IF NOT EXISTS `moloni_es_api_config`( 
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
                config VARCHAR(100), 
                description VARCHAR(100), 
                selected VARCHAR(100), 
                changed TIMESTAMP default CURRENT_TIMESTAMP
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;'
        );
    }

    /**
     * Create Moloni account settings
     */
    private static function insertSettings()
    {
        global $wpdb;
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('document_set_id', 'Choose a Document Set for better organization')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('exemption_reason', 'Choose a Tax Exemption for products that do not have taxes')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('exemption_reason_shipping', 'Choose a Tax Exemption for shipping that does not have taxes')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('payment_method', 'Choose a default payment method')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('measure_unit', 'Choose the unit of measurement to use')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('maturity_date', 'Maturity date')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('document_status', 'Choose the status of the document (closed or in draft)')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('document_type', 'Choose the type of documents you want to issue')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('tax_id', 'Choose a rate to apply to products')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('tax_id_shipping', 'Choose a rate to apply to shipping')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, selected, description) VALUES('client_prefix', 'WC', 'Customer reference prefix')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('product_prefix', 'Product reference prefix')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('update_final_consumer', 'Update customer')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('shipping_info', 'Shipping info')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('vat_field', 'VAT')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('email_send', 'Send e-mail')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('moloni_stock_sync', 'Sync Stocks')");
        $wpdb->query("INSERT INTO `moloni_es_api_config`(config, description) VALUES('moloni_product_sync', 'Sync products')");
    }
}
