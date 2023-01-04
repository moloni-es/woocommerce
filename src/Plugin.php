<?php

namespace MoloniES;

use MoloniES\Helpers\Context;
use MoloniES\Helpers\WebHooks;
use MoloniES\WebHooks\WebHook;
use MoloniES\Controllers\Documents;
use MoloniES\Controllers\PendingOrders;
use MoloniES\Hooks\WoocommerceInitialize;
use WC_Order;

/**
 * Main constructor
 * Class Plugin
 * @package Moloni
 */
class Plugin
{
    /**
     * Plugin constructor.
     */
    public function __construct()
    {
        $this->onStart();
        $this->translations();
        $this->actions();
    }

    //            Privates            //

    /**
     * Place to run code before starting
     *
     * @return void
     */
    private function onStart()
    {
        Storage::$USES_NEW_ORDERS_SYSTEM = Context::isNewOrdersSystemEnabled();
    }

    /**
     * Loads translations
     */
    private function translations()
    {
        //loads translations files
        load_plugin_textdomain('moloni_es', FALSE, basename(dirname(MOLONI_ES_PLUGIN_FILE)) . '/languages/');
    }

    /**
     * Starts needed classes
     */
    private function actions()
    {
        new WoocommerceInitialize($this);
        new Menus\Admin($this);
        new Hooks\ProductUpdate($this);
        new Hooks\ProductView($this);
        new Hooks\OrderView($this);
        new Hooks\OrderPaid($this);
        new WebHooks\WebHook();
        new Ajax($this);
    }

    //            Publics            //

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

                switch ($action) {
                    case 'remInvoice':
                        $this->removeOrder();
                        break;

                    case 'remInvoiceAll':
                        $this->removeOrdersAll();
                        break;

                    case 'reinstallWebhooks':
                        $this->reinstallWebhooks();
                        break;

                    case 'genInvoice':
                        $document = $this->createDocument();
                        break;

                    case 'syncStocks':
                        $this->syncStocks();
                        break;

                    case 'remLogs':
                        $this->removeLogs();
                        break;

                    case 'getInvoice':
                        $this->openDocument();
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

    //            Actions            //

    /**
     * Create a new document
     *
     * @return Documents
     *
     * @throws Error
     */
    private function createDocument()
    {
        $orderId = (int)(sanitize_text_field($_REQUEST['id']));

        $document = new Documents($orderId);
        $document->createDocument();

        if ($document->documentId) {
            $viewUrl = ' <a href="' . esc_url(admin_url('admin.php?page=molonies&action=getInvoice&id=' . $document->documentId)) . '" target="_BLANK">' . __('View document', 'moloni_es') . '</a>';
            add_settings_error('molonies', 'moloni-document-created-success', __('Document was created!', 'moloni_es') . $viewUrl, 'updated');
        }

        return $document;
    }

    /**
     * Open Moloni document
     *
     * @return void
     *
     * @throws Error
     */
    private function openDocument()
    {
        $document = false;
        $documentId = (int)(sanitize_text_field($_REQUEST['id']));

        if ($documentId > 0) {
            $document = Documents::showDocument($documentId);
        }

        if (!$document) {
            add_settings_error('molonies', 'moloni-document-not-found', __('Document not found.', 'moloni_es'));
        }
    }

    /**
     * Delete logs
     *
     * @return void
     */
    private function removeLogs()
    {
        Log::removeLogs();

        add_settings_error('molonies', 'moloni-rem-logs', __('Logs cleanup is complete.', 'moloni_es'), 'updated');
    }

    /**
     * Remove order from pending list
     */
    private function removeOrder()
    {
        $orderId = (int)(sanitize_text_field($_GET['id']));

        if (isset($_GET['confirm']) && sanitize_text_field($_GET['confirm']) === 'true') {
            $order = wc_get_order($orderId);
            $order->add_meta_data('_molonies_sent', '-1');
            $order->add_order_note(__('Order marked as created'));
            $order->save();

            add_settings_error(
                'molonies',
                'moloni-order-remove-success',
                sprintf(__('Order %s has been marked as generated!', 'moloni_es'), $orderId),
                'updated'
            );
        } else {
            add_settings_error(
                'molonies',
                'moloni-order-remove',
                sprintf(__('Do you confirm that you want to mark the order %s as paid?', 'moloni_es'), $orderId) . " <a href='" . esc_url(admin_url('admin.php?page=molonies&action=remInvoice&confirm=true&id=' . $orderId)) . "'>" . __('Yes, i confirm', 'moloni_es') . "</a>"
            );
        }
    }

    /**
     * Removes all orders form the pending list
     */
    private function removeOrdersAll()
    {
        if (isset($_GET['confirm']) && sanitize_text_field($_GET['confirm']) === 'true') {
            /** @var WC_Order[] $allOrders */
            $allOrders = PendingOrders::getAllAvailable();

            if (!empty($allOrders)) {
                foreach ($allOrders as $order) {
                    $order->add_meta_data('_molonies_sent', '-1');
                    $order->add_order_note(__('Order marked as created'));
                    $order->save();
                }

                add_settings_error('molonies', 'moloni-order-all-remove-success', __('All orders have been marked as generated!', 'moloni_es'), 'updated');
            } else {
                add_settings_error('molonies', 'moloni-order-all-remove-not-found', __('No order found to generate!', 'moloni_es'));
            }
        } else {
            $url = esc_url(admin_url('admin.php?page=molonies&action=remInvoiceAll&confirm=true'));

            add_settings_error(
                'molonies', 'moloni-order-remove',
                __('Do you confirm that you want to mark all orders as generated?', 'moloni_es') . " <a href='" . $url . "'>" . __('Yes, i confirm', 'moloni_es') . "</a>"
            );
        }
    }

    /**
     * Forces stock synchronization
     */
    private function syncStocks()
    {
        $date = isset($_GET['since']) ? sanitize_text_field($_GET['since']) : gmdate('Y-m-d', strtotime('-1 week'));

        $syncStocksResult = (new Controllers\SyncProducts($date))->run();

        if ($syncStocksResult->countUpdated() > 0) {
            add_settings_error('molonies', 'moloni-sync-stocks-updated', sprintf(__('%s products updated.', 'moloni_es'), $syncStocksResult->countUpdated()), 'updated');
        }

        if ($syncStocksResult->countEqual() > 0) {
            add_settings_error('molonies', 'moloni-sync-stocks-updated', sprintf(__('There are %s products up to date.', 'moloni_es'), $syncStocksResult->countEqual()), 'updated');
        }

        if ($syncStocksResult->countNotFound() > 0) {
            add_settings_error('molonies', 'moloni-sync-stocks-not-found', sprintf(__('%s products were not found in WooCommerce.', 'moloni_es'), $syncStocksResult->countNotFound()));
        }
    }

    /**
     * Reinstall Moloni Webhooks
     */
    private function reinstallWebhooks()
    {
        try {
            WebHooks::deleteHooks();
            WebHooks::createHooks();

            $msg = __('Moloni Webhooks reinstalled successfully.', 'moloni_es');
            $type = 'updated';
        } catch (Error $e) {
            $msg = __('Something went wrong reinstalling Moloni Webhooks.', 'moloni_es');
            $type = 'error';
        }

        add_settings_error('molonies', 'moloni-webhooks-reinstall-error', $msg, $type);
    }
}
