<?php

namespace MoloniES\Hooks;

use Exception;
use MoloniES\Exceptions\HookException;
use WC_Product;
use MoloniES\Exceptions\APIExeption;
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
        $this->wcProductId = $wcProductId;

        if (!Start::login(true) || !$this->shouldRunHook()) {
            return;
        }

        $wcProduct = $this->fetchWcProduct($wcProductId);

        try {
            if (!$this->wooCommerceProductIsValid($wcProduct)) {
                throw new HookException(__('WooCommerce product not valid', 'moloni_es'));
            }

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

                        if (!$this->wooCommerceProductIsValid($wcVariation)) {
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
            $message .= '.';

            Storage::$LOGGER->error($message, [
                'tag' => 'automatic:product:save:error',
                'exception' => $e->getMessage(),
                'data' => [
                    'wcProductId' => $wcProductId,
                    'request' => $e->getData(),
                ]
            ]);
        } catch (Exception $e) {
            Storage::$LOGGER->critical(__('Fatal error', 'moloni_es'), [
                'tag' => 'automatic:product:save:fatalerror',
                'exception' => $e->getMessage(),
                'data' => [
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

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id());
        SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $moloniProduct['productId']);

        $service = new UpdateSimpleProduct($wcProduct, $moloniProduct);
        $service->run();
        $service->saveLog();
    }

    /**
     * Creator action
     *
     * @throws ServiceException
     */
    private function createVariant(WC_Product $wcProduct)
    {
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
     */
    private function updateVariant(WC_Product $wcProduct, array $moloniProduct)
    {
        if (empty($moloniProduct['variants']) && $moloniProduct['deletable'] === false) {
            throw new HookException(__('Product types do not match', 'moloni_es'));
        }

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id());
        SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $moloniProduct['productId']);

        $service = new UpdateVariantProduct($wcProduct, $moloniProduct);
        $service->run();
        $service->saveLog();
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

    private function shouldRunHook(): bool
    {
        return defined('MOLONI_PRODUCT_SYNC') && (int)MOLONI_PRODUCT_SYNC === Boolean::YES;
    }

    private function wooCommerceProductIsValid(?WC_Product $wcProduct): bool
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

        if (SyncLogs::hasTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id())) {
            return false;
        }

        return true;
    }
}
