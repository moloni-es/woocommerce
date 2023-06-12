<?php

namespace MoloniES\Services\Exports;

use Exception;
use MoloniES\Exceptions\HelperException;
use MoloniES\Services\MoloniProduct\Helpers\Variants\FindVariant;
use MoloniES\Services\MoloniProduct\Helpers\Variants\GetOrUpdatePropertyGroup;
use WC_Product;
use MoloniES\Enums\Boolean;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Storage;
use MoloniES\Tools\SyncLogs;
use MoloniES\Enums\SyncLogsType;
use MoloniES\Exceptions\Core\MoloniException;
use MoloniES\Services\MoloniProduct\Stock\SyncProductStock;

class ExportStockChanges extends ExportService
{
    public function run()
    {
        /**
         * @see https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
         */
        $filters = [
            'status' => ['publish'],
            'limit' => $this->itemsPerPage,
            'page' => $this->page,
            'paginate' => true,
            'orderby' => [
                'ID' => 'DESC',
            ],
        ];

        $wcProducts = wc_get_products($filters);

        $this->totalResults = (int)$wcProducts->total;

        /**
         * @var $wcProduct WC_Product
         */
        foreach ($wcProducts->products as $wcProduct) {
            try {
                $moloniProduct = $this->fetchMoloniProduct($wcProduct);
            } catch (APIExeption $e) {
                $this->errorProducts[] = [$wcProduct->get_id() => 'Error fetching product.'];

                continue;
            }

            if (empty($moloniProduct)) {
                $this->errorProducts[] = [$wcProduct->get_id() => 'Product does not exist in Moloni.'];

                continue;
            }

            if ((int)$moloniProduct['hasStock'] === Boolean::NO) {
                $this->errorProducts[] = [$wcProduct->get_id() => 'Moloni product does not manage stock.'];

                continue;
            }

            if ($wcProduct->is_type('variable')) {
                /** Both need to be the same kind */
                if (!empty($moloniProduct['variants']) !== $wcProduct->is_type('variable')) {
                    $this->errorProducts[] = [$wcProduct->get_id() => 'Product types do not match'];

                    continue;
                }

                $childIds = $wcProduct->get_children();

                $targetId = $moloniProduct['propertyGroup']['propertyGroupId'] ?? '';

                try {
                    $propertyGroup = (new GetOrUpdatePropertyGroup($wcProduct, $targetId))->handle();
                } catch (HelperException $e) {
                    $this->errorProducts[] = [$wcProduct->get_id() => 'Error getting or updating property group.'];

                    continue;
                }

                /** Give timeouts to parent's as well */
                SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProduct->get_id());
                SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_STOCK, $moloniProduct['productId']);

                foreach ($childIds as $childId) {
                    $wcVariation = wc_get_product($childId);

                    if (!$wcVariation->managing_stock()) {
                        $this->errorProducts[] = [$wcVariation->get_id() => 'WooCommerce product does not manage stock.'];

                        continue;
                    }

                    $moloniVariant = (new FindVariant(
                        $wcProduct->get_id(),
                        $wcVariation->get_sku(),
                        $moloniProduct['variants'],
                        $propertyGroup['variants'][$childId] ?? []
                    ))->run();


                    if (empty($moloniVariant)) {
                        $this->errorProducts[] = [$wcProduct->get_id() => 'Moloni variant not found.'];

                        continue;
                    }

                    $this->syncProduct($wcVariation, $moloniVariant);
                }
            } else {
                if (!$wcProduct->managing_stock()) {
                    $this->errorProducts[] = [$wcProduct->get_id() => 'WooCommerce product does not manage stock.'];

                    continue;
                }

                $this->syncProduct($wcProduct, $moloniProduct);
            }
        }

        Storage::$LOGGER->info(sprintf(__('Products stock export. Part %s', 'moloni_es'), $this->page), [
                'tag' => 'tool:export:stock',
                'success' => $this->syncedProducts,
                'error' => $this->errorProducts,
            ]
        );
    }

    private function syncProduct($wcProductOrVariation, $moloniProductOrVariant)
    {
        try {
            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProductOrVariation->get_id());
            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_STOCK, $moloniProductOrVariant['productId']);

            $service = new SyncProductStock($wcProductOrVariation, $moloniProductOrVariant);
            $service->run();

            $this->syncedProducts[] = [$wcProductOrVariation->get_id() => $service->getResultMsg()];
        } catch (MoloniException|Exception $e) {
            $this->errorProducts[] = [$wcProductOrVariation->get_id() => $e->getMessage()];
        }
    }
}
