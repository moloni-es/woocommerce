<?php

namespace MoloniES\Activators;

class Remove
{
    public static function run()
    {
        global $wpdb;
        $wpdb->query("DROP TABLE moloni_es_api");
        $wpdb->query("DROP TABLE moloni_es_api_config");
        wp_clear_scheduled_hook('moloniEsProductsSync');
    }

}
