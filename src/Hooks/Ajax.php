<?php

namespace MoloniES\Hooks;

use Exception;
use MoloniES\API\Companies;
use MoloniES\API\Products;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\SyncLogsType;
use MoloniES\Exceptions\Core\MoloniException;
use MoloniES\Exceptions\DocumentError;
use MoloniES\Exceptions\DocumentWarning;
use MoloniES\Exceptions\GenericException;
use MoloniES\Helpers\MoloniWarehouse;
use MoloniES\Plugin;
use MoloniES\Services\Exports\ExportProducts;
use MoloniES\Services\Exports\ExportStockChanges;
use MoloniES\Services\Imports\ImportProducts;
use MoloniES\Services\Imports\ImportStockChanges;
use MoloniES\Services\Orders\CreateMoloniDocument;
use MoloniES\Services\Orders\DiscardOrder;
use MoloniES\Services\WcProduct\Create\CreateChildProduct;
use MoloniES\Services\WcProduct\Create\CreateParentProduct;
use MoloniES\Start;
use MoloniES\Storage;
use MoloniES\Tools\SyncLogs;

class Ajax
{
    public $parent;

    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('wp_ajax_genInvoice', [$this, 'genInvoice']);
        add_action('wp_ajax_discardOrder', [$this, 'discardOrder']);

        add_action('wp_ajax_toolsMassImportStock', [$this, 'toolsMassImportStock']);
        add_action('wp_ajax_toolsMassImportProduct', [$this, 'toolsMassImportProduct']);
        add_action('wp_ajax_toolsMassExportStock', [$this, 'toolsMassExportStock']);
        add_action('wp_ajax_toolsMassExportProduct', [$this, 'toolsMassExportProduct']);

        add_action('wp_ajax_toolsCreateWcProduct', [$this, 'toolsCreateWcProduct']);
        add_action('wp_ajax_toolsUpdateWcStock', [$this, 'toolsUpdateWcStock']);
        add_action('wp_ajax_toolsCreateMoloniProduct', [$this, 'toolsCreateMoloniProduct']);
        add_action('wp_ajax_toolsUpdateMoloniStock', [$this, 'toolsUpdateMoloniStock']);
    }

    //             Publics             //

    public function genInvoice()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new CreateMoloniDocument((int)$_REQUEST['id']);
        $orderName = $service->getOrderNumber() ?? '';

        try {
            $service->run();

            $response = [
                'valid' => 1,
                'message' => sprintf(__('Document %s successfully inserted', 'moloni_es'), $service->getOrderNumber())
            ];
        } catch (DocumentWarning $e) {
            $message = sprintf(__('There was an warning when generating the document (%s)'), $orderName);
            $message .= ' </br>';
            $message .= $e->getMessage();

            Storage::$LOGGER->alert($message, [
                    'tag' => 'ajax:document:create:warning',
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );

            $response = ['valid' => 1, 'message' => $e->getMessage(), 'data' => $e->getData()];
        } catch (DocumentError $e) {
            $message = sprintf(__('There was an error when generating the document (%s)'), $orderName);
            $message .= ' </br>';
            $message .= strip_tags($e->getMessage());

            Storage::$LOGGER->error($message, [
                    'tag' => 'ajax:document:create:error',
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );

            $response = ['valid' => 0, 'message' => $e->getMessage(), 'data' => $e->getData()];
        } catch (Exception $e) {
            Storage::$LOGGER->critical(__("Fatal error", 'moloni_es'), [
                'tag' => 'ajax:document:create:fatalerror',
                'message' => $e->getMessage()
            ]);

            $response = ['valid' => 0, 'message' => $e->getMessage()];
        }

        $this->sendJson($response);
    }

    public function discardOrder()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $response = [
            'valid' => 1
        ];

        $order = wc_get_order((int)$_REQUEST['id']);

        $service = new DiscardOrder($order);
        $service->run();
        $service->saveLog();

        $this->sendJson($response);
    }


    public function toolsMassImportStock()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new ImportStockChanges((int)$_REQUEST['page']);
        $service->run();

        $response = [
            'valid' => 1,
            'overlayContent' => '',
            'hasMore' => $service->getHasMore(),
            'totalResults' => $service->getTotalResults(),
            'currentPercentage' => $service->getCurrentPercentage()
        ];

        $response['overlayContent'] = $this->loadModalContent($response);

        $this->sendJson($response);
    }

    public function toolsMassImportProduct()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new ImportProducts((int)$_REQUEST['page']);
        $service->run();

        $response = [
            'valid' => 1,
            'overlayContent' => '',
            'hasMore' => $service->getHasMore(),
            'totalResults' => $service->getTotalResults(),
            'currentPercentage' => $service->getCurrentPercentage()
        ];

        $response['overlayContent'] = $this->loadModalContent($response);

        $this->sendJson($response);
    }

    public function toolsMassExportStock()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new ExportStockChanges((int)$_REQUEST['page']);
        $service->run();

        $response = [
            'valid' => 1,
            'overlayContent' => '',
            'hasMore' => $service->getHasMore(),
            'totalResults' => $service->getTotalResults(),
            'currentPercentage' => $service->getCurrentPercentage()
        ];

        $response['overlayContent'] = $this->loadModalContent($response);

        $this->sendJson($response);
    }

    public function toolsMassExportProduct()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new ExportProducts((int)$_REQUEST['page']);
        $service->run();

        $response = [
            'valid' => 1,
            'overlayContent' => '',
            'hasMore' => $service->getHasMore(),
            'totalResults' => $service->getTotalResults(),
            'currentPercentage' => $service->getCurrentPercentage()
        ];

        $response['overlayContent'] = $this->loadModalContent($response);

        $this->sendJson($response);
    }


    public function toolsCreateWcProduct()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $mlProductId = (int)($_POST['ml_product_id'] ?? 0);
        $response = [
            'valid' => 1,
            'message' => '',
            'product_row' => '',
            'post' => [
                'ml_product_id' => $mlProductId,
                'action' => 'toolsCreateWcProduct'
            ]
        ];

        try {
            $mlProduct = Products::queryProduct(['productId' => $mlProductId])['data']['product']['data'] ?? [];

            if (empty($mlProduct)) {
                throw new GenericException(__('Product not found in Moloni account', 'moloni_es'));
            }

            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $mlProductId);

            if (empty($mlProduct['variants'])) {
                $service = new CreateParentProduct($mlProduct);
                $service->run();
                $service->saveLog();

                $wcParentProduct = $service->getWcProduct();

                foreach ($mlProduct['variants'] as $variant) {
                    if ((int)$variant['visible'] === Boolean::NO) {
                        continue;
                    }

                    $service = new CreateChildProduct($variant, $wcParentProduct);
                    $service->run();
                    $service->saveLog();
                }
            } else {
                $service = new \MoloniES\Services\WcProduct\Create\CreateSimpleProduct($mlProduct);
                $service->run();
                $service->saveLog();
            }

            $warehouseId = defined('HOOK_STOCK_SYNC_WAREHOUSE') ? (int)HOOK_STOCK_SYNC_WAREHOUSE : 1;
            $company = Companies::queryCompany()['data']['company']['data'] ?? [];

            $checkService = new \MoloniES\Services\MoloniProduct\Page\CheckProduct($mlProduct, $warehouseId, $company);
            $checkService->run();

            $response['product_row'] = $checkService->getRowsHtml();
        } catch (MoloniException $e) {
            $response['valid'] = 0;
            $response['message'] = $e->getMessage();
        }

        $this->sendJson($response);
    }

    public function toolsUpdateWcStock()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $mlProductId = (int)($_POST['ml_product_id'] ?? 0);
        $wcProductId = (int)($_POST['wc_product_id'] ?? 0);
        $response = [
            'valid' => 1,
            'message' => '',
            'product_row' => '',
            'post' => [
                'ml_product_id' => $mlProductId,
                'wc_product_id' => $wcProductId,
                'action' => 'toolsUpdateWcStock'
            ]
        ];

        try {
            $mlProduct = Products::queryProduct(['productId' => $mlProductId])['data']['product']['data'] ?? [];

            if (empty($mlProduct)) {
                throw new GenericException(__('Product not found in Moloni account', 'moloni_es'));
            }

            $wcProduct = wc_get_product($wcProductId);

            if (empty($wcProduct)) {
                throw new GenericException(__('Product not found in WooCommerce store', 'moloni_es'));
            }

            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProductId);
            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_STOCK, $mlProductId);

            $service = new \MoloniES\Services\WcProduct\Stock\SyncProductStock($mlProduct, $wcProduct);
            $service->run();
            $service->saveLog();

            $warehouseId = defined('HOOK_STOCK_SYNC_WAREHOUSE') ? (int)HOOK_STOCK_SYNC_WAREHOUSE : 1;
            $company = Companies::queryCompany()['data']['company']['data'] ?? [];

            $checkService = new \MoloniES\Services\MoloniProduct\Page\CheckProduct($mlProduct, $warehouseId, $company);
            $checkService->run();

            $response['product_row'] = $checkService->getRowsHtml();
        } catch (MoloniException $e) {
            $response['valid'] = 0;
            $response['message'] = $e->getMessage();
        }

        $this->sendJson($response);
    }

    public function toolsCreateMoloniProduct()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $wcProductId = (int)($_POST['wc_product_id'] ?? 0);
        $response = [
            'valid' => 1,
            'message' => '',
            'product_row' => '',
            'post' => [
                'wc_product_id' => $wcProductId,
                'action' => 'toolsCreateMoloniProduct'
            ]
        ];

        $wcProduct = wc_get_product($wcProductId);

        try {
            if (empty($wcProduct)) {
                throw new GenericException(__('Product not found in WooCommerce store', 'moloni_es'));
            }

            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProductId);

            if ($wcProduct->is_type('variable') && $wcProduct->has_child()) {
                $service = new \MoloniES\Services\MoloniProduct\Create\CreateVariantProduct($wcProduct);
            } else {
                $service = new \MoloniES\Services\MoloniProduct\Create\CreateSimpleProduct($wcProduct);
            }

            $service->run();
            $service->saveLog();

            $company = Companies::queryCompany()['data']['company']['data'] ?? [];
            $warehouseId = defined('MOLONI_STOCK_SYNC_WAREHOUSE') ? (int)MOLONI_STOCK_SYNC_WAREHOUSE : 0;

            if (empty($warehouseId)) {
                $warehouseId = MoloniWarehouse::getDefaultWarehouseId();
            }

            $checkService = new \MoloniES\Services\WcProduct\Page\CheckProduct($wcProduct, $warehouseId, $company);
            $checkService->run();

            $response['product_row'] = $checkService->getRowsHtml();
        } catch (MoloniException $e) {
            $response['valid'] = 0;
            $response['message'] = $e->getMessage();
        }

        $this->sendJson($response);
    }

    public function toolsUpdateMoloniStock()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $mlProductId = (int)($_POST['ml_product_id'] ?? 0);
        $wcProductId = (int)($_POST['wc_product_id'] ?? 0);
        $response = [
            'valid' => 1,
            'message' => '',
            'product_row' => '',
            'post' => [
                'ml_product_id' => $mlProductId,
                'wc_product_id' => $wcProductId,
                'action' => 'toolsUpdateMoloniStock'
            ]
        ];

        try {

            $wcProduct = wc_get_product($wcProductId);

            if (empty($wcProduct)) {
                throw new GenericException(__('Product not found in WooCommerce store', 'moloni_es'));
            }

            $mlProduct = Products::queryProduct(['productId' => $mlProductId])['data']['product']['data'] ?? [];

            if (empty($mlProduct)) {
                throw new GenericException(__('Product not found in Moloni account', 'moloni_es'));
            }

            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProductId);
            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_STOCK, $mlProductId);

            $service = new \MoloniES\Services\MoloniProduct\Stock\SyncProductStock($wcProduct, $mlProduct);
            $service->run();
            $service->saveLog();

            $company = Companies::queryCompany()['data']['company']['data'] ?? [];
            $warehouseId = defined('MOLONI_STOCK_SYNC_WAREHOUSE') ? (int)MOLONI_STOCK_SYNC_WAREHOUSE : 0;

            if (empty($warehouseId)) {
                $warehouseId = MoloniWarehouse::getDefaultWarehouseId();
            }

            $checkService = new \MoloniES\Services\WcProduct\Page\CheckProduct($wcProduct, $warehouseId, $company);
            $checkService->run();

            $response['product_row'] = $checkService->getRowsHtml();
        } catch (MoloniException $e) {
            $response['valid'] = 0;
            $response['message'] = $e->getMessage();
        }

        $this->sendJson($response);
    }

    //             Privates             //

    private function isAuthed(): bool
    {
        return Start::login(true);
    }

    /**
     * Load tools modal content
     *
     * @see https://wpadmin.bracketspace.com/
     */
    private function loadModalContent($data)
    {
        ob_start();

        include MOLONI_ES_TEMPLATE_DIR . 'Modals/Products/Blocks/ActionModalContent.php';

        return ob_get_clean();
    }

    /**
     * Return and stop execution afterward.
     *
     * @see https://developer.wordpress.org/reference/hooks/wp_ajax_action/
     *
     * @param array $data
     * @return void
     */
    private function sendJson(array $data)
    {
        wp_send_json($data);
        wp_die();
    }
}
