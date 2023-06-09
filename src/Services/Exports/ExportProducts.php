<?php

namespace MoloniES\Services\Exports;

use Exception;
use WC_Product;
use MoloniES\Storage;
use MoloniES\Tools\SyncLogs;
use MoloniES\Enums\SyncLogsType;
use MoloniES\Exceptions\Core\MoloniException;
use MoloniES\Services\MoloniProduct\Create\CreateSimpleProduct;
use MoloniES\Services\MoloniProduct\Create\CreateVariantProduct;

class ExportProducts extends ExportService
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
            if (empty($wcProduct->get_sku())) {
                $this->errorProducts[] = [$wcProduct->get_id() => 'Product has no reference in WooCommerce.'];

                continue;
            }

            try {
                $moloniProduct = $this->fetchMoloniProduct($wcProduct);

                if (!empty($moloniProduct)) {
                    $this->errorProducts[] = [$wcProduct->get_id() => 'Product already exists in Moloni.'];

                    continue;
                }

                SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id());

                if ($wcProduct->is_type('variable')) {
                    $service = new CreateVariantProduct($wcProduct);
                } else {
                    $service = new CreateSimpleProduct($wcProduct);
                }

                $service->run();

                $this->syncedProducts[] = [$wcProduct->get_id() => $service->getMoloniProduct()['reference'] ?? '---'];
            } catch (MoloniException|Exception $e) {
                $this->errorProducts[] = [$wcProduct->get_id() => $e->getMessage()];
            }
        }

        Storage::$LOGGER->info(
            sprintf(__('Products export. Part %s', 'moloni_es'), $this->page),
            [
                'success' => $this->syncedProducts,
                'error' => $this->errorProducts,
            ]
        );
    }
}
