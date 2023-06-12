<?php

namespace MoloniES\Hooks;

use WC_Product;
use MoloniES\Start;
use MoloniES\Plugin;
use MoloniES\Storage;
use MoloniES\API\Products;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\SyncLogsType;
use MoloniES\Tools\SyncLogs;
use MoloniES\Tools\ProductAssociations;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\Core\MoloniException;
use MoloniES\Services\MoloniProduct\Stock\SyncProductStock;

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

        try {
            $moloniProduct = $this->fetchMoloniProduct($wcProduct);

            if (empty($moloniProduct) || (int)$moloniProduct['hasStock'] === Boolean::NO) {
                return;
            }

            $service = new SyncProductStock($wcProduct, $moloniProduct);
            $service->run();
            $service->saveLog();
        } catch (MoloniException $e) {
            $message = __('Error synchronizing stock.');
            $message .= ' </br>';
            $message .= $e->getMessage();

            Storage::$LOGGER->error($message, [
                    'tag' => 'automatic:product:stock:error',
                    'message' => $e->getMessage(),
                    'request' => $e->getData()
                ]
            );
        }
    }

    //            Privates            //

    /**
     * Fetch Moloni Product
     *
     * @throws APIExeption
     */
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

        $query = Products::queryProducts($variables);

        $byReference = $query['data']['products']['data'] ?? [];

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
