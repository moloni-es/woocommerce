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
            }
        } else {
            $prefix = $wpdb->get_blog_prefix();

            self::createTables($prefix);
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
			    log_id INT NOT NULL AUTO_INCREMENT,
                type_id INT NOT NULL,
                entity_id INT NOT NULL,
                sync_date VARCHAR(250) CHARACTER SET utf8 NOT NULL,
			    PRIMARY KEY (`log_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;'
        );

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `" . $prefix . "moloni_es_logs` (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                log_level VARCHAR(100) NULL,
                company_id INT,
                message TEXT,
                context TEXT,
                created_at TIMESTAMP default CURRENT_TIMESTAMP
            ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;"
        );

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `" . $prefix . "moloni_es_product_associations` (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                wc_product_id INT(11) NOT NULL,
                wc_parent_id INT(11) DEFAULT 0,
                ml_product_id INT(11) NOT NULL,
                ml_parent_id INT(11) DEFAULT 0,
                active INT(11) DEFAULT 1
            ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;"
        );
    }
}
