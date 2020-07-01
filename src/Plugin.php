<?php

namespace MoloniES;

use MoloniES\Controllers\Documents;
use WC_Order;
use WC_Product;
/**
 * Main constructor
 * Class Plugin
 * @package Moloni
 */
class Plugin
{
    public function __construct()
    {
        $this->defines();
        $this->actions();
        $this->crons();
    }

    /**
     * Define some table params
     * Load scripts and CSS as needed
     */
    private function defines()
    {
        if (isset($_REQUEST['page']) && !wp_doing_ajax() && sanitize_text_field($_REQUEST['page']) === 'molonies') {

            wp_enqueue_style('jquery-modal', plugins_url('assets/external/jquery.modal.min.css', MOLONI_ES_PLUGIN_FILE));
            wp_enqueue_script('jquery-modal', plugins_url('assets/external/jquery.modal.min.js', MOLONI_ES_PLUGIN_FILE));

            wp_enqueue_style('moloni-styles', plugins_url('assets/css/moloni.css', MOLONI_ES_PLUGIN_FILE));
            wp_enqueue_script('moloni-actions-bulk-documentes-js', plugins_url('assets/js/BulkDocuments.js', MOLONI_ES_PLUGIN_FILE));
            //send translated strings to the javascript file
            wp_localize_script('moloni-actions-bulk-documentes-js','translations',[
                'startingProcess' => __('Starting process...','moloni_es'),
                'noOrdersSelected' => __('No orders selected to generate','moloni_es'),
                'creatingDocument' => __('Creating document','moloni_es'),
                'progressCompleted' => __('Progress Completed','moloni_es'),
                'createdDocuments' => __('Documents created: ','moloni_es'),
                'documentsWithErrors' => __('Documents with errors: ','moloni_es')
            ]);
        }
    }

    private function actions()
    {
        new Menus\Admin($this);
        new Hooks\ProductUpdate($this);
        new Hooks\ProductView($this);
        new Hooks\OrderView($this);
        new Hooks\OrderPaid($this);
        new Ajax($this);
    }

    /**
     * Setting up the crons if needed
     */
    private function crons()
    {
        add_filter('cron_schedules', '\MoloniES\Crons::addCronInterval');
        add_action('moloniesProductsSync', '\MoloniES\Crons::productsSync');

        if (!wp_next_scheduled('moloniesProductsSync')) {
            wp_schedule_event(time(), 'everyficeminutes', 'moloniesProductsSync');
        }
    }

    /**
     * Main function
     * This will run when accessing the page "molonies" and the routing shoud be done here with and $_GET['action']
     */
    public function run()
    {
        try {
            /** If the user is not logged in show the login form */
            if (Start::login()) {
                $action = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : '';

                //$array=[88,90,92,94,95,96,110,114];

                //foreach ($array as $id) {
                //    delete_post_meta($id,'_molonies_sent');
                //}

                switch ($action) {
                    case 'remInvoice':
                        $this->removeOrder((int)$_GET['id']);
                        break;

                    case 'remInvoiceAll':
                        $this->removeOrdersAll();
                        break;

                    case 'genInvoice':
                        $orderId = (int)$_REQUEST['id'];
                        /** @noinspection PhpUnusedLocalVariableInspection */
                        $document = $this->createDocument($orderId);
                        break;

                    case 'syncStocks':
                        $this->syncStocks();
                        break;

                    case 'remLogs':
                        Log::removeLogs();
                        add_settings_error('molonies', 'moloni-rem-logs', __('Logs cleanup is complete.','moloni_es'), 'updated');
                        break;

                    case 'getInvoice':
                        $document = false;
                        $documentId = (int)$_REQUEST['id'];

                        if ($documentId > 0) {
                            $document = Documents::showDocument($documentId);
                        }

                        if (!$document) {
                            add_settings_error('molonies', 'moloni-document-not-found', __('Document not found.','moloni_es'));
                        }
                        break;
                }

                if (!wp_doing_ajax()) {
                    include MOLONI_ES_TEMPLATE_DIR . 'MainContainer.php';
                }
            }
        } catch (Error $error) {
            $error->showError();
        }
    }

    /**
     * @param $orderId
     * @return Documents
     * @throws Error
     */
    private function createDocument($orderId)
    {
        $document = new Documents($orderId);
        $document->createDocument();

        if ($document->documentId) {
            $viewUrl = ' <a href="' . esc_url(admin_url('admin.php?page=molonies&action=getInvoice&id=' . $document->documentId)) . '" target="_BLANK">'. __('View document','moloni_es') .'</a>';
            add_settings_error('molonies', 'moloni-document-created-success', __('Document was created!','moloni_es') . $viewUrl, 'updated');
        }

        return $document;
    }

    /**
     * @param int $orderId
     */
    private function removeOrder($orderId)
    {
        if (isset($_GET['confirm']) && sanitize_text_field($_GET['confirm']) === 'true') {
            add_post_meta($orderId, '_molonies_sent', '-1', true);
            add_settings_error(
                'molonies',
                'moloni-order-remove-success',
                sprintf(__('Order %s has been marked as generated!','moloni_es'),$orderId),
                'updated'
            );
        } else {
            add_settings_error(
                'molonies',
                'moloni-order-remove',
                sprintf( __('Do you confirm that you want to mark the order %s as paid?','moloni_es'), $orderId ). " <a href='" . esc_url(admin_url('admin.php?page=molonies&action=remInvoice&confirm=true&id=' . $orderId)) . "'>" . __('Yes, i confirm','moloni_es') . "</a>"
            );
        }
    }


    private function removeOrdersAll()
    {
        if (isset($_GET['confirm']) && sanitize_text_field($_GET['confirm']) === 'true') {
            $allOrders = Controllers\PendingOrders::getAllAvailable();
            if (!empty($allOrders) && is_array($allOrders)) {
                foreach ($allOrders as $order) {
                    add_post_meta($order['id'], '_molonies_sent', '-1', true);
                }
                add_settings_error('molonies', 'moloni-order-all-remove-success', __('All orders have been marked as generated!' , 'moloni_es'), 'updated');
            } else {
                add_settings_error('molonies', 'moloni-order-all-remove-not-found', __('No order found to generate!' , 'moloni_es'));
            }
        } else {
            add_settings_error(
                'molonies', 'moloni-order-remove', __('Do you confirm that you want to mark all orders as generated?', 'moloni_es') . " <a href='" . esc_url(admin_url('admin.php?page=molonies&action=remInvoiceAll&confirm=true')) . "'>" . __('Yes, i confirm','moloni_es') . "</a>"
            );
        }
    }

    private function syncStocks()
    {
        $date = isset($_GET['since']) ? sanitize_text_field($_GET['since']) : gmdate('Y-m-d', strtotime('-1 week'));

        $syncStocksResult = (new Controllers\SyncProducts($date))->run();

        if ($syncStocksResult->countUpdated() > 0) {
            add_settings_error('molonies', 'moloni-sync-stocks-updated', sprintf(__('%s products updated.', 'moloni_es') , $syncStocksResult->countUpdated()), 'updated');
        }

        if ($syncStocksResult->countEqual() > 0) {
            add_settings_error('molonies', 'moloni-sync-stocks-updated', sprintf(__('There are %s products up to date.','moloni_es') , $syncStocksResult->countEqual()), 'updated');
        }

        if ($syncStocksResult->countNotFound() > 0) {
            add_settings_error('molonies', 'moloni-sync-stocks-not-found', sprintf(__('%s products were not found in WooCommerce.','moloni_es'),$syncStocksResult->countNotFound()));
        }
    }
}
