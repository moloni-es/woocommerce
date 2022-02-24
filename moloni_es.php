<?php
/**
 *
 *   Plugin Name:  Moloni España
 *   Description:  Simple invoicing integration with Moloni ES
 *   Version:      1.0.30
 *   Author:       Moloni.es
 *   Author URI:   https://moloni.es
 *   License:      GPL2
 *   License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 *   Text Domain:  moloni_es
 *   Domain Path:  /languages
 *
 */

namespace MoloniES;

if (!defined('ABSPATH')) {
    exit;
}

$composer_autoloader = __DIR__ . '/vendor/autoload.php';
if (is_readable($composer_autoloader)) {
    /** @noinspection PhpIncludeInspection */
    require $composer_autoloader;
}

if (!defined('MOLONI_ES_PLUGIN_FILE')) {
    define('MOLONI_ES_PLUGIN_FILE', __FILE__);
}

if (!defined('MOLONI_ES_DIR')) {
    define('MOLONI_ES_DIR', __DIR__);
}

if (!defined('MOLONI_ES_TEMPLATE_DIR')) {
    define('MOLONI_ES_TEMPLATE_DIR', __DIR__ . '/src/Templates/');
}

if (!defined('MOLONI_ES_PLUGIN_URL')) {
    define('MOLONI_ES_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('MOLONI_ES_IMAGES_URL')) {
    define('MOLONI_ES_IMAGES_URL', plugin_dir_url(__FILE__) . 'images/');
}

register_activation_hook(__FILE__, '\MoloniES\Activators\Install::run');
register_deactivation_hook(__FILE__, '\MoloniES\Activators\Remove::run');

add_action('plugins_loaded', Start::class);
add_action('admin_enqueue_scripts', '\MoloniES\Scripts\Enqueue::defines');

function Start()
{
    //start the plugin
    return new Plugin();
}
