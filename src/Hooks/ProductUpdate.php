<?php

namespace MoloniES\Hooks;

use Exception;
use MoloniES\Exceptions\HelperException;
use MoloniES\Services\MoloniProduct\Helpers\Variants\FindVariantByProperties;
use MoloniES\Services\MoloniProduct\Helpers\Variants\ParseProductProperties;
use MoloniES\Services\MoloniProduct\Stock\SyncProductStock;
use WC_Product;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\HookException;
use MoloniES\Exceptions\ServiceException;
use MoloniES\Traits\SettingsTrait;
use MoloniES\API\Products;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\SyncLogsType;
use MoloniES\Exceptions\Core\MoloniException;
use MoloniES\Notice;
use MoloniES\Plugin;
use MoloniES\Services\MoloniProduct\Create\CreateSimpleProduct;
use MoloniES\Services\MoloniProduct\Create\CreateVariantProduct;
use MoloniES\Services\MoloniProduct\Update\UpdateSimpleProduct;
use MoloniES\Services\MoloniProduct\Update\UpdateVariantProduct;
use MoloniES\Start;
use MoloniES\Storage;
use MoloniES\Tools\ProductAssociations;
use MoloniES\Tools\SyncLogs;

class ProductUpdate
{
    use SettingsTrait;

    /**
     * Main class
     *
     * @var Plugin
     */
    public $parent;

    /**
     * WooCommerce product ID
     *
     * @var int|null
     */
    private $wcProductId = 0;

    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('woocommerce_update_product', [$this, 'productSave']);
    }

    public function productSave($wcProductId)
    {
        /** WooCommerce product has some type of timeout */
        if (SyncLogs::hasTimeout([SyncLogsType::WC_PRODUCT_SAVE, SyncLogsType::WC_PRODUCT_STOCK], $wcProductId)) {
            return;
        }

        /** Login is valid */
        if (!Start::login(true)) {
            return;
        }

        /** No sincronization is active */
        if (!$this->shouldSyncProduct() && !$this->shouldSyncStock()) {
            return;
        }

        $this->wcProductId = $wcProductId;

        $wcProduct = $this->fetchWcProduct($wcProductId);

        try {
            $this->validateWcProduct($wcProduct);

            if ($wcProduct->is_type('variable')) {
                if ($this->isSyncProductWithVariantsActive()) {
                    $moloniProduct = $this->fetchMoloniProduct($wcProduct);

                    if (empty($moloniProduct)) {
                        $this->createVariant($wcProduct);
                    } else {
                        $this->updateVariant($wcProduct, $moloniProduct);
                    }
                } else {
                    $childIds = $wcProduct->get_children();

                    foreach ($childIds as $childId) {
                        $wcVariation = $this->fetchWcProduct($childId);

                        if (!$this->wcVariationIsValid($wcVariation)) {
                            continue;
                        }

                        $moloniProduct = $this->fetchMoloniProduct($wcVariation);

                        if (empty($moloniProduct)) {
                            $this->createSimple($wcVariation);
                        } else {
                            $this->updateSimple($wcVariation, $moloniProduct);
                        }
                    }
                }
            } else {
                $moloniProduct = $this->fetchMoloniProduct($wcProduct);

                if (empty($moloniProduct)) {
                    $this->createSimple($wcProduct);
                } else {
                    $this->updateSimple($wcProduct, $moloniProduct);
                }
            }
        } catch (MoloniException $e) {
            Notice::addmessagecustom(htmlentities($e->geterror()));

            $message = __('Error synchronizing products to Moloni.', 'moloni_es');
            $message .= ' </br>';
            $message .= $e->getMessage();

            if (!in_array(substr($message, -1), ['.', '!', '?'])) {
                $message .= '.';
            }

            Storage::$LOGGER->error($message, [
                'tag' => 'automatic:product:save:error',
                'message' => $e->getMessage(),
                'extra' => [
                    'wcProductId' => $wcProductId,
                    'data' => $e->getData(),
                ]
            ]);
        } catch (Exception $e) {
            Storage::$LOGGER->critical(__('Fatal error', 'moloni_es'), [
                'tag' => 'automatic:product:save:fatalerror',
                'message' => $e->getMessage(),
                'extra' => [
                    'wcProductId' => $wcProductId,
                ]
            ]);
        }
    }

    //          Actions          //

    /**
     * Creator action
     *
     * @throws ServiceException
     */
    private function createSimple(WC_Product $wcProduct)
    {
        if (!$this->shouldSyncProduct()) {
            return;
        }

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id());

        $service = new CreateSimpleProduct($wcProduct);
        $service->run();
        $service->saveLog();
    }

    /**
     * Updater action
     *
     * @throws ServiceException
     * @throws HookException
     */
    private function updateSimple(WC_Product $wcProduct, array $moloniProduct)
    {
        if (!empty($moloniProduct['variants']) && $moloniProduct['deletable'] === false) {
            throw new HookException(__('Product types do not match', 'moloni_es'));
        }

        if ($this->shouldSyncProduct()) {
            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id());
            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $moloniProduct['productId']);

            $service = new UpdateSimpleProduct($wcProduct, $moloniProduct);
            $service->run();
            $service->saveLog();

            $moloniProduct = $service->getMoloniProduct();
        }

        if ($this->shouldSyncStock() && $wcProduct->managing_stock() && (int)$moloniProduct['hasStock'] === Boolean::YES) {
            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProduct->get_id());
            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_STOCK, $moloniProduct['productId']);

            $service = new SyncProductStock($wcProduct, $moloniProduct);
            $service->run();
            $service->saveLog();
        }
    }

    /**
     * Creator action
     *
     * @throws ServiceException
     */
    private function createVariant(WC_Product $wcProduct)
    {
        if (!$this->shouldSyncProduct()) {
            return;
        }

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id());

        $service = new CreateVariantProduct($wcProduct);
        $service->run();
        $service->saveLog();
    }

    /**
     * Updater action
     *
     * @throws ServiceException
     * @throws HookException
     * @throws HelperException
     */
    private function updateVariant(WC_Product $wcProduct, array $moloniProduct)
    {
        if (empty($moloniProduct['variants']) && $moloniProduct['deletable'] === false) {
            throw new HookException(__('Product types do not match', 'moloni_es'));
        }

        if ($this->shouldSyncProduct()) {
            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id());
            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $moloniProduct['productId']);

            $service = new UpdateVariantProduct($wcProduct, $moloniProduct);
            $service->run();
            $service->saveLog();

            $moloniProduct = $service->getMoloniProduct();
        }

        if ($this->shouldSyncStock() && (int)$moloniProduct['hasStock'] === Boolean::YES) {
            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProduct->get_id());
            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_STOCK, $moloniProduct['productId']);

            $wcProductAttributes = (new ParseProductProperties($wcProduct))->handle();
            $childIds = $wcProduct->get_children();

            foreach ($childIds as $childId) {
                $moloniVariant = [];
                $wcVariation = wc_get_product($childId);

                if (!$wcVariation->managing_stock()) {
                    continue;
                }

                $association = ProductAssociations::findByWcId($wcVariation->get_id());

                if (!empty($association)) {
                    foreach ($moloniProduct['variants'] as $variant) {
                        if ((int)$variant['productId'] === (int)$association['ml_product_id']) {
                            $moloniVariant = $variant;

                            break;
                        }
                    }
                }

                if (empty($moloniVariant)) {
                    $wcTargetProductAttributes = $wcProductAttributes[$wcVariation->get_id()] ?? [];
                    $moloniVariant = (new FindVariantByProperties($wcTargetProductAttributes, $moloniProduct))->handle();
                }

                if (empty($moloniVariant)) {
                    continue;
                }

                SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcVariation->get_id());

                $service = new SyncProductStock($wcVariation, $moloniVariant);
                $service->run();
                $service->saveLog();
            }
        }
    }

    //          Privatess          //

    /**
     * Fetch WooCommerce product
     */
    private function fetchWcProduct($wcProductId): WC_Product
    {
        return wc_get_product($wcProductId);
    }

    /**
     * Fetch Moloni product
     *
     * @throws APIExeption
     */
    private function fetchMoloniProduct(WC_Product $wcProduct): array
    {
        /** Fetch by our associations table */

        $association = ProductAssociations::findByWcId($wcProduct->get_id());

        if (!empty($association)) {
            $byId = Products::queryProduct(['productId' => (int)$association['ml_product_id']]);
            $byId = $byId['data']['product']['data'] ?? [];

            if (!empty($byId)) {
                return $byId;
            }

            ProductAssociations::deleteById($association['id']);
        }

        if (empty($wcProduct->get_sku())) {
            return [];
        }

        $variables = [
            'options' => [
                'filter' => [
                    [
                        'field' => 'reference',
                        'comparison' => 'eq',
                        'value' => $wcProduct->get_sku(),
                    ],
                    [
                        'field' => 'visible',
                        'comparison' => 'in',
                        'value' => '[0, 1]'
                    ]
                ]
            ]
        ];

        $query = Products::queryProducts($variables);

        $byReference = $query['data']['products']['data'] ?? [];

        if (!empty($byReference) && isset($byReference[0]['productId'])) {
            return $byReference[0];
        }

        return [];
    }

    //          Auxiliary          //

    private function shouldSyncProduct(): bool
    {
        return defined('MOLONI_PRODUCT_SYNC') && (int)MOLONI_PRODUCT_SYNC === Boolean::YES;
    }

    private function shouldSyncStock(): bool
    {
        return defined('MOLONI_STOCK_SYNC') && (int)MOLONI_STOCK_SYNC === Boolean::YES;
    }

    //          Validations          //

    /**
     * Validate WooCommerce product
     *
     * @throws HookException
     */
    private function validateWcProduct(?WC_Product $wcProduct)
    {
        if (empty($wcProduct)) {
            throw new HookException(__('Product not found', 'moloni_es'));
        }

        if ($wcProduct->get_status() === 'draft') {
            throw new HookException(__('Product is not published', 'moloni_es'));
        }

        if (empty($wcProduct->get_sku())) {
            throw new HookException(__('Product does not have reference', 'moloni_es'));
        }
    }

    /**
     * Validate WooCommerce variation
     */
    private function wcVariationIsValid(?WC_Product $wcProduct): bool
    {
        if (empty($wcProduct)) {
            return false;
        }

        if ($wcProduct->get_status() === 'draft') {
            return false;
        }

        if (empty($wcProduct->get_sku())) {
            return false;
        }

        if (SyncLogs::hasTimeout([SyncLogsType::WC_PRODUCT_SAVE, SyncLogsType::WC_PRODUCT_STOCK], $wcProduct->get_id())) {
            return false;
        }

        return true;
    }
}
