<?php

namespace MoloniES;

use MoloniES\Enums\Boolean;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\Core\MoloniException;
use MoloniES\Exceptions\DocumentError;
use MoloniES\Exceptions\DocumentWarning;
use MoloniES\Helpers\Context;
use MoloniES\Helpers\WebHooks;
use MoloniES\Hooks\Ajax;
use MoloniES\Hooks\OrderList;
use MoloniES\Hooks\OrderPaid;
use MoloniES\Hooks\OrderView;
use MoloniES\Hooks\ProductDelete;
use MoloniES\Hooks\ProductSetStock;
use MoloniES\Hooks\ProductUpdate;
use MoloniES\Hooks\ProductView;
use MoloniES\Hooks\UpgradeProcess;
use MoloniES\Hooks\WoocommerceInitialize;
use MoloniES\Menus\Admin;
use MoloniES\Models\Logs;
use MoloniES\Models\PendingOrders;
use MoloniES\Services\Documents\DownloadDocumentPDF;
use MoloniES\Services\Documents\OpenDocument;
use MoloniES\Services\Orders\CreateMoloniDocument;
use MoloniES\Services\Orders\DiscardOrder;
use MoloniES\Tools\Logger;
use MoloniES\WebHooks\WebHook;
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
        Storage::$LOGGER = new Logger();
    }

    /**
     * Loads translations
     */
    private function translations()
    {
        /** Loads translations files */
        load_plugin_textdomain('moloni_es', FALSE, basename(dirname(MOLONI_ES_PLUGIN_FILE)) . '/languages/');
    }

    /**
     * Starts needed classes
     */
    private function actions()
    {
        /** Admin pages */
        new Admin($this);
        new Ajax($this);

        /** Webservices */
        new WebHook();

        /** Hooks */
        new ProductUpdate($this);
        new ProductDelete($this);
        new ProductView($this);
        new ProductSetStock($this);
        new OrderView($this);
        new OrderPaid($this);
        new OrderList($this);
        new UpgradeProcess($this);
        new WoocommerceInitialize($this);
    }

    //            Publics            //

    /**
     * Main function
     * This will run when accessing the page "molonies" and the routing shoud be done here with and $_GET['action']
     */
    public function run()
    {
        try {
            $authenticated = Start::login();

            /** If the user is not logged in show the login form */
            if ($authenticated) {
                $action = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : '';

                switch ($action) {
                    case 'remInvoice':
                        $this->removeOrder();
                        break;

                    case 'reinstallWebhooks':
                        $this->reinstallWebhooks();
                        break;

                    case 'genInvoice':
                        $this->createDocument();
                        break;

                    case 'remLogs':
                        $this->removeLogs();
                        break;

                    case 'getInvoice':
                        $this->openDocument();
                        break;
                    case 'downloadDocument':
                        $this->downloadDocument();
                        break;
                }
            }
        } catch (MoloniException $error) {
            $pluginErrorException = $error;
        }

        if (isset($authenticated) && $authenticated && !wp_doing_ajax()) {
            include MOLONI_ES_TEMPLATE_DIR . 'MainContainer.php';
        }
    }

    //            Actions            //

    /**
     * Create a new document
     *
     * @throws DocumentWarning|DocumentError|MoloniException
     */
    private function createDocument()
    {
        $service = new CreateMoloniDocument((int)(sanitize_text_field($_REQUEST['id'])));
        $orderName = $service->getOrderNumber();

        try {
            $service->run();
        } catch (DocumentWarning $e) {
            $message = sprintf(__('There was an warning when generating the document (%s)'), $orderName);
            $message .= ' </br>';
            $message .= $e->getMessage();

            Storage::$LOGGER->alert($message, [
                    'tag' => 'service:document:create:manual:warning',
                    'message' => $e->getMessage(),
                    'request' => $e->getData()
                ]
            );

            throw $e;
        } catch (DocumentError $e) {
            $message = sprintf(__('There was an error when generating the document (%s)'), $orderName);
            $message .= ' </br>';
            $message .= strip_tags($e->getMessage());

            Storage::$LOGGER->error($message, [
                    'tag' => 'service:document:create:manual:error',
                    'message' => $e->getMessage(),
                    'request' => $e->getData()
                ]
            );

            throw $e;
        }

        if ($service->getDocumentId() > 0) {
            $viewUrl = ' <a href="' . esc_url(admin_url('admin.php?page=molonies&action=getInvoice&id=' . $service->getDocumentId())) . '" target="_BLANK">' . __('View document', 'moloni_es') . '</a>';

            add_settings_error('molonies', 'moloni-document-created-success', __('Document was created!', 'moloni_es') . $viewUrl, 'updated');
        }
    }

    /**
     * Open Moloni document
     *
     * @return void
     */
    private function openDocument()
    {
        $documentId = (int)(sanitize_text_field($_REQUEST['id']));

        if ($documentId > 0) {
            new OpenDocument($documentId);
        }

        add_settings_error('molonies', 'moloni-document-not-found', __('Document not found.', 'moloni_es'));
    }

    /**
     * Download Moloni document
     *
     * @return void
     */
    private function downloadDocument(): void
    {
        $documentId = (int)$_REQUEST['id'];

        if ($documentId > 0) {
            new DownloadDocumentPDF($documentId);
        }
    }

    /**
     * Delete logs
     *
     * @return void
     */
    private function removeLogs()
    {
        Logs::removeOlderLogs();

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

            $service = new DiscardOrder($order);
            $service->run();
            $service->saveLog();

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
     * Reinstall Moloni Webhooks
     */
    private function reinstallWebhooks()
    {
        try {
            WebHooks::deleteHooks();

            if (defined('HOOK_STOCK_SYNC') && (int)HOOK_STOCK_SYNC === Boolean::YES) {
                WebHooks::createHook('Product', 'stockChanged');
            }

            if (defined('HOOK_PRODUCT_SYNC') && (int)HOOK_PRODUCT_SYNC === Boolean::YES) {
                WebHooks::createHook('Product', 'create');
                WebHooks::createHook('Product', 'update');
            }

            $msg = __('Moloni Webhooks reinstalled successfully.', 'moloni_es');
            $type = 'updated';
        } catch (APIExeption $e) {
            $msg = __('Something went wrong reinstalling Moloni Webhooks.', 'moloni_es');
            $type = 'error';
        }

        add_settings_error('molonies', 'moloni-webhooks-reinstall-error', $msg, $type);
    }
}
