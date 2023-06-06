<?php

namespace MoloniES\Hooks;

use Exception;
use MoloniES\Exceptions\ServiceException;
use WC_Product;
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
    private $wcProductId;

    /**
     * WooCommerce product
     *
     * @var WC_Product|null
     */
    private $wcProduct;

    /**
     * Moloni product
     *
     * @var array
     */
    private $moloniProduct = [];

    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('woocommerce_update_product', [$this, 'productSave']);
    }

    public function productSave($wcProductId)
    {
        if (!Start::login(true)) {
            return;
        }

        $this->wcProductId = $wcProductId;

        if (!$this->isProductSyncActive() || $this->productHasTimeout()) {
            return;
        }

        $this->fetchWcProduct();

        if (!$this->productIsValidToSync()) {
            return;
        }

        $this->fetchMoloniProduct();

        try {
            if (empty($this->moloniProduct)) {
                $this->create();
            } else {
                $this->update();
            }
        } catch (MoloniException $e) {
            Notice::addmessagecustom(htmlentities($e->geterror()));

            Storage::$LOGGER->error(__('Error synchronizing products to Moloni', 'moloni_es'), [
                'action' => 'automatic:product:save',
                'exception' => $e->getMessage(),
                'data' => [
                    'wcProductId' => $this->wcProductId,
                    'moloniProduct' => $this->moloniProduct,
                    'request' => $e->getData(),
                ]
            ]);
        } catch (Exception $e) {
            Storage::$LOGGER->critical(__('Fatal error', 'moloni_es'), [
                'action' => 'automatic:product:save',
                'exception' => $e->getMessage(),
                'data' => [
                    'wcProductId' => $this->wcProductId,
                    'moloniProduct' => $this->moloniProduct,
                ]
            ]);
        }
    }

    //          Privates          //

    /**
     * Creator action
     *
     * @throws ServiceException
     */
    private function create()
    {
        if ($this->wcProduct->is_type('variable')) {
            $service = new CreateVariantProduct($this->wcProduct);
        } else {
            $service = new CreateSimpleProduct($this->wcProduct);
        }

        $service->run();
        $service->saveLog();
    }

    /**
     * Updater action
     *
     * @throws ServiceException
     */
    private function update()
    {
        if ($this->wcProduct->is_type('variable')) {
            $service = new UpdateVariantProduct($this->wcProduct, $this->moloniProduct);
        } else {
            $service = new UpdateSimpleProduct($this->wcProduct, $this->moloniProduct);
        }

        $service->run();
        $service->saveLog();
    }

    private function fetchWcProduct()
    {
        $this->wcProduct = wc_get_product($this->wcProductId);
    }

    private function fetchMoloniProduct(): void
    {
        /** Fetch by our associations table */

        $association = ProductAssociations::findByWcId($this->wcProductId);

        if (!empty($association)) {
            $byId = Products::queryProduct(['productId' => (int)$association['ml_product_id']]);
            $byId = $byId['data']['product']['data'] ?? [];

            if (!empty($byId)) {
                $this->moloniProduct = $byId;

                return;
            }

            ProductAssociations::deleteById($association['id']);
        }

        $variables = [
            'options' => [
                'filter' => [
                    [
                        'field' => 'reference',
                        'comparison' => 'eq',
                        'value' => $this->wcProduct->get_sku(),
                    ],
                    [
                        'field' => 'visible',
                        'comparison' => 'gte',
                        'value' => '0',
                    ]
                ],
                "includeVariants" => true
            ]
        ];

        $byReference = Products::queryProducts($variables);

        if (!empty($byReference) && isset($byReference[0]['productId'])) {
            $this->moloniProduct = $byReference[0];
        }
    }

    //          Auxiliary          //

    private function productIsValidToSync(): bool
    {
        if (
            empty($this->wcProduct) ||
            $this->wcProduct->get_status() === 'draft' ||
            empty($this->wcProduct->get_sku()) ||
            $this->wcProduct->get_parent_id() > 0
        ) {
            return false;
        }

        return true;
    }

    private function productHasTimeout(): bool
    {
        if (SyncLogs::hasTimeout(SyncLogsType::WC_PRODUCT_SAVE, $this->wcProductId)) {
            return true;
        }

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $this->wcProductId);

        return false;
    }

    private function isProductSyncActive(): bool
    {
        return defined('MOLONI_PRODUCT_SYNC') && (int)MOLONI_PRODUCT_SYNC === Boolean::YES;
    }
}
