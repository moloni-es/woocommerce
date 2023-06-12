<?php

namespace MoloniES\Services\Exports;

use Exception;
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
            if ($wcProduct->is_type('variable')) {
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

                /** Both need to be the same kind */
                if (!empty($moloniProduct['variants']) !== $wcProduct->is_type('variable')) {
                    $this->errorProducts[] = [$wcProduct->get_id() => 'Product types do not match'];

                    continue;
                }

                $childIds = $wcProduct->get_children();

                foreach ($childIds as $childId) {
                    $wcVariation = wc_get_product($childId);

                    if (!$wcVariation->managing_stock()) {
                        $this->errorProducts[] = [$wcVariation->get_id() => 'WooCommerce product does not manage stock.'];

                        continue;
                    }

                    $moloniVariant = [];

                    // todo: find variant here
                    // todo: copy from order product, from line 558 till 576

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

                $this->syncProduct($wcProduct, $moloniProduct);
            }
        }

        Storage::$LOGGER->info(
            sprintf(__('Products stock export. Part %s', 'moloni_es'), $this->page),
            [
                'success' => $this->syncedProducts,
                'error' => $this->errorProducts,
            ]
        );
    }

    private function syncProduct($wcProduct, $moloniProduct)
    {
        try {
            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProduct->get_id());

            $service = new SyncProductStock($wcProduct, $moloniProduct);
            $service->run();

            $this->syncedProducts[] = [$wcProduct->get_id() => $service->getResultMsg()];
        } catch (MoloniException|Exception $e) {
            $this->errorProducts[] = [$wcProduct->get_id() => $e->getMessage()];
        }
    }
}
