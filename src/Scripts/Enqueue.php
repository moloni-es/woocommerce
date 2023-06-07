<?php

namespace MoloniES\Scripts;

/**
 * Class Enqueue
 * Add script files to queue
 *
 * @package Moloni\Scripts
 */
class Enqueue
{
    /**
     * Define some table params
     * Load scripts and CSS as needed
     */
    public static function defines()
    {
        if (isset($_REQUEST['page']) && !wp_doing_ajax() && sanitize_text_field($_REQUEST['page']) === 'molonies') {
            $tab = $_GET['tab'] ?? '';

            wp_enqueue_script('jquery', plugins_url('assets/external/jquery-3.6.0.min.js', MOLONI_ES_PLUGIN_FILE));
            wp_enqueue_style('jquery-modal', plugins_url('assets/external/jquery.modal.min.css', MOLONI_ES_PLUGIN_FILE));
            wp_enqueue_script('jquery-modal', plugins_url('assets/external/jquery.modal.min.js', MOLONI_ES_PLUGIN_FILE));

            wp_enqueue_style('moloni-styles', plugins_url('assets/css/moloni.css', MOLONI_ES_PLUGIN_FILE));

            if ($tab === 'settings') {
                wp_enqueue_script('moloni-settings-js', plugins_url('assets/js/Moloni.Settings.js', MOLONI_ES_PLUGIN_FILE));
            }

            if ($tab === 'automation') {
                wp_enqueue_script('moloni-automations-js', plugins_url('assets/js/Moloni.Automations.js', MOLONI_ES_PLUGIN_FILE));
            }

            if ($tab === 'logs') {
                wp_enqueue_script('moloni-settings-js', plugins_url('assets/js/Moloni.Logs.js', MOLONI_ES_PLUGIN_FILE));
            }

            if ($tab === 'tools') {
                wp_enqueue_script('moloni-tools-js', plugins_url('assets/js/Moloni.Tools.js', MOLONI_ES_PLUGIN_FILE));
            }

            if (empty($tab)) {
                wp_enqueue_script('moloni-actions-bulk-actions-js', plugins_url('assets/js/OrdersBulkAction.js', MOLONI_ES_PLUGIN_FILE));

                /** Send translated strings to the javascript file */
                wp_localize_script('moloni-actions-bulk-actions-js', 'translations', [
                    'startingProcess' => __('Starting process...', 'moloni_es'),
                    'noOrdersSelected' => __('No orders selected to process', 'moloni_es'),
                    'creatingDocument' => __('Creating document', 'moloni_es'),
                    'discardingOrder' => __('Discarding order', 'moloni_es'),
                    'createdDocuments' => __('Documents created:', 'moloni_es'),
                    'documentsWithErrors' => __('Documents with errors:', 'moloni_es'),
                    'discardedOrders' => __('Orders discarded:', 'moloni_es'),
                    'ordersWithErrors' => __('Orders with errors:', 'moloni_es'),
                    'close' => __('Close', 'moloni_es'),
                ]);
            }
        }
    }
}
