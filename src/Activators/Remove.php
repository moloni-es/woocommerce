<?php

namespace MoloniES\Activators;

use WP_Site;

class Remove
{
    public static function run(): void
    {
        global $wpdb;

        if (is_multisite() && function_exists('get_sites')) {
            /** @var WP_Site[] $sites */
            $sites = get_sites();

            foreach ($sites as $site) {
                self::dropTables($wpdb->get_blog_prefix($site->blog_id));
            }
        } else {
            self::dropTables($wpdb->get_blog_prefix());
        }

        wp_clear_scheduled_hook('moloniEsProductsSync');
    }

    public static function uninitializeSite(WP_Site $site): void
    {
        global $wpdb;

        self::dropTables($wpdb->get_blog_prefix($site->blog_id));
    }

    private static function dropTables($prefix = null): void
    {
        global $wpdb;

        $wpdb->query("DROP TABLE " . $prefix . "moloni_es_api");
        $wpdb->query("DROP TABLE " . $prefix . "moloni_es_api_config");
        $wpdb->query("DROP TABLE " . $prefix . "moloni_es_logs");
        $wpdb->query("DROP TABLE " . $prefix . "moloni_es_sync_logs");
    }
}
