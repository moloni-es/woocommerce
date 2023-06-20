<?php

namespace MoloniES\Services\Imports;

use MoloniES\Exceptions\HelperException;
use WC_Product;
use Exception;
use MoloniES\API\Products;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\SyncLogsType;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Helpers\References;
use MoloniES\Services\MoloniProduct\Helpers\Variants\ParseProductProperties;
use MoloniES\Services\WcProduct\Helpers\Variations\FindVariation;
use MoloniES\Services\WcProduct\Stock\SyncProductStock;
use MoloniES\Storage;
use MoloniES\Tools\SyncLogs;

class ImportStockChanges extends ImportService
{
    public function run(): void
    {
        $props = [
            'options' => [
                'order' => [
                    'field' => 'reference',
                    'sort' => 'DESC',
                ],
                'filter' => [
                    'field' => 'hasStock',
                    'comparison' => 'eq',
                    'value' => '1',
                ],
                'pagination' => [
                    'page' => $this->page,
                    'qty' => $this->itemsPerPage,
                ]
            ]
        ];

        try {
            $query = Products::queryProducts($props);
        } catch (APIExeption $e) {
            return;
        }

        $this->totalResults = (int)($query['data']['products']['options']['pagination']['count'] ?? 0);

        $data = $query['data']['products']['data'] ?? [];

        foreach ($data as $product) {
            if (References::isIgnoredReference($product['reference'])) {
                $this->errorProducts[] = [$product['reference'] => 'Reference is blacklisted'];

                continue;
            }

            if (!empty($product['variants']) && !$this->isSyncProductWithVariantsActive()) {
                $this->errorProducts[] = [$product['reference'] => 'Synchronization of products with variants is disabled'];

                continue;
            }

            $wcProduct = $this->fetchWcProduct($product);

            if (empty($wcProduct)) {
                $this->errorProducts[] = [$product['reference'] => 'Product does not exist in WooCommerce'];

                continue;
            }

            /** Timeout both products */
            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProduct->get_id());
            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_STOCK, $product['productId']);

            try {
                if (empty($product['variants'])) {
                    $this->importProductSimpleStock($wcProduct, $product);
                } else {
                    $this->importProductWithVariantsStock($wcProduct, $product);
                }
            } catch (Exception $exception) {
                $this->errorProducts[] = [$product['reference'] => $exception->getMessage()];
            }
        }

        Storage::$LOGGER->info(sprintf(__('Products stock import. Part %s', 'moloni_es'), $this->page), [
                'tag' => 'tool:import:stock',
                'success' => $this->syncedProducts,
                'error' => $this->errorProducts,
                'settings' => [
                    'syncProductWithVariations' => $this->isSyncProductWithVariantsActive()
                ]
            ]
        );
    }

    //              Privates              //

    private function importProductSimpleStock(WC_Product $wcProduct, array $product)
    {
        /** Product type cannot be "variable", so every other one is valid (like simple or even a variation) */
        if ($wcProduct->is_type('variable')) {
            $this->errorProducts[] = [$product['reference'] => 'Product types do not match'];

            return;
        }

        if (!$wcProduct->managing_stock()) {
            $this->errorProducts[] = [$product['reference'] => 'Product does not manage stock'];

            return;
        }

        $service = new SyncProductStock($product, $wcProduct);
        $service->run();

        $this->syncedProducts[] = [$product['reference'] => $service->getResultMsg()];
    }

    /**
     * Import product stock from product with variation
     *
     * @throws HelperException
     */
    private function importProductWithVariantsStock(WC_Product $wcProduct, array $product)
    {
        /** Both need to be the same kind */
        if (!$wcProduct->is_type('variable')) {
            $this->errorProducts[] = [$product['reference'] => 'Product types do not match'];

            return;
        }

        $wcParentAttributes = (new ParseProductProperties($wcProduct))->handle();

        foreach ($product['variants'] as $variant) {
            $auxVariantIdentifier = $product['reference'] . ':' . $variant['reference'];

            if ((int)$variant['visible'] === Boolean::NO || (int)$variant['hasStock'] === Boolean::NO) {
                $this->errorProducts[] = [$auxVariantIdentifier => 'Variant is not visible'];

                continue;
            }

            $wcProductVariation = (new FindVariation($wcParentAttributes, $variant))->run();

            if (empty($wcProductVariation)) {
                $this->errorProducts[] = [$auxVariantIdentifier => 'Variation not found in WooCommerce'];

                continue;
            }

            if (!$wcProductVariation->managing_stock()) {
                $this->errorProducts[] = [$auxVariantIdentifier => 'Variation does not manage stock in WooCommerce'];

                continue;
            }

            if ($wcProductVariation->get_parent_id() !== $wcProduct->get_id()) {
                $this->errorProducts[] = [$auxVariantIdentifier => 'Variation found does not match parent product'];

                continue;
            }

            $service = new SyncProductStock($variant, $wcProductVariation);
            $service->run();

            $this->syncedProducts[] = [$auxVariantIdentifier => $service->getResultMsg()];
        }
    }
}
