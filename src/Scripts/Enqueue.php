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
            $ver = '2.0';

            wp_enqueue_style('moloni-styles', plugins_url('assets/css/moloni.min.css', MOLONI_ES_PLUGIN_FILE), [], $ver);
            wp_enqueue_script('moloni-scripts', plugins_url('assets/js/moloni.min.js', MOLONI_ES_PLUGIN_FILE), [], $ver);
        }
    }
}
