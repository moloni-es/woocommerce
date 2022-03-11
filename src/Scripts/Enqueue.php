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
    public function defines()
    {
        if (isset($_REQUEST['page']) && !wp_doing_ajax() && sanitize_text_field($_REQUEST['page']) === 'molonies') {

            wp_enqueue_script('jquery', plugins_url('assets/external/jquery-3.6.0.min.js', MOLONI_ES_PLUGIN_FILE));
            wp_enqueue_style('jquery-modal', plugins_url('assets/external/jquery.modal.min.css', MOLONI_ES_PLUGIN_FILE));
            wp_enqueue_script('jquery-modal', plugins_url('assets/external/jquery.modal.min.js', MOLONI_ES_PLUGIN_FILE));

            wp_enqueue_style('moloni-styles', plugins_url('assets/css/moloni.css', MOLONI_ES_PLUGIN_FILE));

            if ($_GET['tab'] === 'settings') {
                wp_enqueue_script('moloni-settings-js', plugins_url('assets/js/Moloni.Settings.js', MOLONI_ES_PLUGIN_FILE));
            }

            if (!in_array($_GET['tab'], ['settings', 'tools', 'automation'])) {
                wp_enqueue_script('moloni-actions-bulk-documentes-js', plugins_url('assets/js/BulkDocuments.js', MOLONI_ES_PLUGIN_FILE));

                //send translated strings to the javascript file
                wp_localize_script('moloni-actions-bulk-documentes-js', 'translations', [
                    'startingProcess' => __('Starting process...', 'moloni_es'),
                    'noOrdersSelected' => __('No orders selected to generate', 'moloni_es'),
                    'creatingDocument' => __('Creating document', 'moloni_es'),
                    'progressCompleted' => __('Progress Completed', 'moloni_es'),
                    'createdDocuments' => __('Documents created: ', 'moloni_es'),
                    'documentsWithErrors' => __('Documents with errors: ', 'moloni_es'),
                    'close' => __('Close', 'moloni_es'),
                ]);
            }
        }
    }
}
