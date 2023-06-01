<?php

namespace MoloniES\Hooks;

use WC_Product;
use MoloniES\API\Products;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\SyncLogsType;
use MoloniES\Plugin;
use MoloniES\Services\MoloniProduct\Stock\SyncProductStock;
use MoloniES\Start;
use MoloniES\Tools\ProductAssociations;
use MoloniES\Tools\SyncLogs;

class ProductSetStock
{
    public $parent;

    /**
     * Constructor
     *
     * @see https://stackoverflow.com/questions/39294861/which-hooks-are-triggered-when-woocommerce-product-stock-is-updated
     */
    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('woocommerce_product_set_stock', [$this, 'woocommerceSetStock']);
        add_action('woocommerce_variation_set_stock', [$this, 'woocommerceSetStock']);
    }

    public function woocommerceSetStock(WC_Product $wcProduct)
    {
        if (!Start::login(true)) {
            return;
        }

        if (!$this->productIsValidToSync($wcProduct)) {
            return;
        }

        $moloniProduct = $this->fetchMoloniProduct($wcProduct);

        if (empty($moloniProduct)) {
            return;
        }

        $service = new SyncProductStock($wcProduct, $moloniProduct);
        $service->run();
        $service->saveLog();
    }

    //            Privates            //

    private function fetchMoloniProduct(WC_Product $wcProduct): array
    {
        /** Fetch by our associaitons table */

        $association = ProductAssociations::findByWcId($wcProduct->get_id());

        if (!empty($association)) {
            $byId = Products::queryProduct(['productId' => $association['ml_product_id']]);
            $byId = $byId['data']['product']['data'] ?? [];

            if (!empty($byId)) {
                return $byId;
            }

            ProductAssociations::deleteById($association['id']);
        }

        $wcSku = $wcProduct->get_sku();

        if (empty($wcSku)) {
            return [];
        }

        $variables = [
            'options' => [
                'filter' => [
                    [
                        'field' => 'reference',
                        'comparison' => 'eq',
                        'value' => $wcSku,
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
            return $byReference[0];
        }

        return [];
    }

    //          Auxiliary          //

    private function productIsValidToSync(?WC_Product $wcProduct): bool
    {
        if (!defined('MOLONI_STOCK_SYNC') || (int)MOLONI_STOCK_SYNC === Boolean::NO) {
            return false;
        }

        if (empty($wcProduct) || $wcProduct->get_status() === 'draft') {
            return false;
        }

        $wcProductId = $wcProduct->get_id();

        if (SyncLogs::hasTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProductId)) {
            return false;
        }

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProductId);

        return true;
    }
}