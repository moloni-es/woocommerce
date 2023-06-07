<?php

namespace MoloniES\Services\Imports;

use Exception;
use MoloniES\API\Products;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\SyncLogsType;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Helpers\References;
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
                continue;
            }

            $wcProduct = $this->fetchWcProduct($product);

            if (empty($wcProduct)) {
                $this->errorProducts[] = [$product['reference'] => 'Product does not exist in WooCommerce'];

                continue;
            }

            /** Both need to be the same kind */
            if (!empty($product['variants']) !== $wcProduct->is_type('variable')) {
                $this->errorProducts[] = [$product['reference'] => 'Product types do not match'];

                continue;
            }

            /** Timeout both products */
            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProduct->get_id());
            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_STOCK, $product['productId']);

            try {
                if (empty($product['variants'])) {
                    if (!$wcProduct->managing_stock()) {
                        $this->errorProducts[] = [$product['reference'] => 'Product does not manage stock'];

                        continue;
                    }

                    $service = new SyncProductStock($product, $wcProduct);
                    $service->run();

                    $this->syncedProducts[] = [$product['reference'] => $service->getResultMsg()];
                } else {
                    foreach ($product['variants'] as $variant) {
                        $auxVariantIdentifier = $product['reference'] . ':' . $variant['reference'];

                        if ((int)$variant['visible'] === Boolean::NO || (int)$variant['hasStock'] === Boolean::NO) {
                            $this->errorProducts[] = [$auxVariantIdentifier => 'Variant is not visible'];

                            continue;
                        }

                        $wcProductVariation = $this->fetchWcProductVariation($variant);

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
            } catch (Exception $exception) {
                $this->errorProducts[] = [$product['reference'] => $exception->getMessage()];
            }
        }

        Storage::$LOGGER->info(
            sprintf(__('Products stock import. Part %s', 'moloni_es'), $this->page),
            [
                'success' => $this->syncedProducts,
                'error' => $this->errorProducts,
            ]
        );
    }
}
