<?php

namespace MoloniES;

use Exception;
use MoloniES\Controllers\SyncProducts;

/**
 * This crons will run in isolation
 */
class Crons
{
    public static function addCronInterval()
    {
        $schedules['everyficeminutes'] = array(
            'interval' => 300,
            'display' => __('Every 5 minutes','moloni_es')
        );
        return $schedules;
    }

    /**
     * @return bool
     * @global $wpdb
     */
    public static function productsSync()
    {
        global $wpdb;
        $runningAt = time();
        try {
            self::requires();

            if (!Start::login(true)) {
                Log::write(__('Could not establish a connection to a Moloni company','moloni_es'));
                return false;
            }

            if (defined('MOLONI_STOCK_SYNC') && MOLONI_STOCK_SYNC) {
                Log::write(__('Starting automatic stock synchronization...','moloni_es'));
                if (!defined('MOLONI_STOCK_SYNC_TIME')) {
                    define('MOLONI_STOCK_SYNC_TIME', (time() - 600));

                    $wpdb->insert($wpdb->get_blog_prefix() . 'moloni_es_api_config', [
                        'config' => 'moloni_stock_sync_time',
                        'selected' => MOLONI_STOCK_SYNC_TIME
                    ]);
                }

                (new SyncProducts(MOLONI_STOCK_SYNC_TIME))->run();
            } else {
                Log::write(__('Stock sync disabled in plugin settings','moloni_es'));
            }

        } catch (Exception $ex) {
            Log::write(__('Fatal error: ','moloni_es') . $ex->getMessage());
        }

        Model::setOption('moloni_stock_sync_time', $runningAt);
        return true;
    }


    public static function requires()
    {
        $composer_autoloader = '../vendor/autoload.php';
        if (is_readable($composer_autoloader)) {
            /** @noinspection PhpIncludeInspection */
            require $composer_autoloader;
        }
    }

}
