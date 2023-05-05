<?php

namespace MoloniES\Activators;

use WP_Site;

class Install
{
    /**
     * Run the installation process
     * Install API Connection table
     * Install Settings table
     * Start sync crons
     */
    public static function run(): void
    {
        if (!function_exists('curl_version')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(esc_html__('cURL library is required for using Moloni Plugin.', 'moloni_es'));
        }

        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(esc_html__('Requires WooCommerce 3.0.0 or above.', 'moloni_es'));
        }

        global $wpdb;

        if (is_multisite() && function_exists('get_sites')) {
            /** @var WP_Site[] $sites */
            $sites = get_sites();

            foreach ($sites as $site) {
                $prefix = $wpdb->get_blog_prefix($site->blog_id);

                self::createTables($prefix);
                self::insertSettings($prefix);
            }
        } else {
            $prefix = $wpdb->get_blog_prefix();

            self::createTables($prefix);
            self::insertSettings($prefix);
        }
    }

    /**
     * Create tables for new site
     *
     * @param WP_Site $site
     *
     * @return void
     */
    public static function initializeSite(WP_Site $site): void
    {
        global $wpdb;

        $prefix = $wpdb->get_blog_prefix($site->blog_id);

        self::createTables($prefix);
        self::insertSettings($prefix);
    }

    /**
     * Create API connection table
     */
    private static function createTables(string $prefix): void
    {
        global $wpdb;
        $wpdb->query(
            'CREATE TABLE IF NOT EXISTS `' . $prefix . 'moloni_es_api`( 
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
                main_token VARCHAR(100), 
                access_expire VARCHAR(250),
                refresh_token VARCHAR(100), 
                refresh_expire VARCHAR(250),
                client_id VARCHAR(100), 
                client_secret VARCHAR(100), 
                company_id INT,
                dated TIMESTAMP default CURRENT_TIMESTAMP
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;'
        );

        $wpdb->query(
            'CREATE TABLE IF NOT EXISTS `' . $prefix . 'moloni_es_api_config`( 
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
                config VARCHAR(100), 
                description VARCHAR(100), 
                selected VARCHAR(100), 
                changed TIMESTAMP default CURRENT_TIMESTAMP
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;'
        );

        $wpdb->query(
            'CREATE TABLE IF NOT EXISTS `' . $prefix . 'moloni_es_sync_logs` (
			    log_id int NOT null AUTO_INCREMENT,
                type_id int NOT null,
                entity_id int NOT null,
                sync_date varchar(250) CHARACTER SET utf8 NOT null,
			    PRIMARY KEY (`log_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;'
        );
    }

    /**
     * Create Moloni account settings
     */
    private static function insertSettings(string $prefix): void
    {
        global $wpdb;

        $wpdb->query("
            INSERT INTO `" . $prefix . "moloni_api_config`(config, description) 
            VALUES 
                ('document_set_id', 'Choose a Document Set for better organization'),
                ('exemption_reason', 'Choose a Tax Exemption for products that do not have taxes'),
                ('exemption_reason_shipping', 'Choose a Tax Exemption for shipping that does not have taxes'),
                ('payment_method', 'Choose a default payment method'),
                ('measure_unit', 'Choose the unit of measurement to use'),
                ('maturity_date', 'Maturity date'),
                ('document_status', 'Choose the status of the document (closed or in draft)'),
                ('document_type', 'Choose the type of documents you want to issue'),
                ('tax_id', 'Choose a rate to apply to products'),
                ('tax_id_shipping', 'Choose a rate to apply to shipping'),
                ('client_prefix', 'WC', 'Customer reference prefix'),
                ('product_prefix', 'Product reference prefix'),
                ('update_final_consumer', 'Update customer'),
                ('shipping_info', 'Shipping info'),
                ('vat_field', 'VAT'),
                ('email_send', 'Send e-mail'),
                ('moloni_stock_sync', 'Sync Stocks'),
                ('moloni_product_sync', 'Sync products')
        ");
    }
}
