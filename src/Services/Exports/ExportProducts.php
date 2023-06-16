<?php

namespace MoloniES\Services\Exports;

use Exception;
use MoloniES\Exceptions\APIExeption;
use WC_Product;
use MoloniES\Storage;
use MoloniES\Tools\SyncLogs;
use MoloniES\Enums\SyncLogsType;
use MoloniES\Exceptions\ServiceException;
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
            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id());

            try {
                if ($wcProduct->is_type('variable')) {
                    if ($this->shouldSyncProductWithVariations()) {
                        $this->createProductWithVariants($wcProduct);
                    } else {
                        $this->createProductWithVariantsAsSimple($wcProduct);
                    }
                } else {
                    $this->createProductSimple($wcProduct);
                }
            } catch (MoloniException|Exception $e) {
                $this->errorProducts[] = [$wcProduct->get_id() => $e->getMessage()];
            }
        }

        Storage::$LOGGER->info(sprintf(__('Products export. Part %s', 'moloni_es'), $this->page), [
                'tag' => 'tool:export:product',
                'success' => $this->syncedProducts,
                'error' => $this->errorProducts,
                'settings' => [
                    'syncProductWithVariations' => $this->shouldSyncProductWithVariations()
                ]
            ]
        );
    }

    //              Privates              //

    /**
     * Create simple product in Moloni
     *
     * @throws ServiceException
     * @throws APIExeption
     */
    private function createProductSimple(WC_Product $wcProduct)
    {
        if (empty($wcProduct->get_sku())) {
            $this->errorProducts[] = [$wcProduct->get_id() => 'Product has no reference in WooCommerce.'];

            return;
        }

        $moloniProduct = $this->fetchMoloniProduct($wcProduct);

        if (!empty($moloniProduct)) {
            $this->errorProducts[] = [$wcProduct->get_id() => 'Product already exists in Moloni.'];

            return;
        }

        $service = new CreateSimpleProduct($wcProduct);
        $service->run();

        $this->syncedProducts[] = [$wcProduct->get_id() => $service->getMoloniProduct()['reference'] ?? '---'];
    }

    /**
     * Create product with variants in Moloni
     *
     * @throws ServiceException
     * @throws APIExeption
     */
    private function createProductWithVariants(WC_Product $wcProduct)
    {
        if (empty($wcProduct->get_sku())) {
            $this->errorProducts[] = [$wcProduct->get_id() => 'Product has no reference in WooCommerce.'];

            return;
        }

        $moloniProduct = $this->fetchMoloniProduct($wcProduct);

        if (!empty($moloniProduct)) {
            $this->errorProducts[] = [$wcProduct->get_id() => 'Product already exists in Moloni.'];

            return;
        }

        $service = new CreateVariantProduct($wcProduct);
        $service->run();

        $this->syncedProducts[] = [$wcProduct->get_id() => $service->getMoloniProduct()['reference'] ?? '---'];
    }

    /**
     * Create WooCommerce variation as simple product in Moloni
     *
     * @throws ServiceException
     * @throws APIExeption
     */
    private function createProductWithVariantsAsSimple(WC_Product $wcProduct)
    {
        $variationIds = $wcProduct->get_children();

        foreach ($variationIds as $variationId) {
            $wcVariation = wc_get_product($variationId);

            if (empty($wcVariation->get_sku())) {
                $this->errorProducts[] = [$wcVariation->get_id() => 'Product has no reference in WooCommerce.'];

                continue;
            }

            $moloniProduct = $this->fetchMoloniProduct($wcVariation);

            if (!empty($moloniProduct)) {
                $this->errorProducts[] = [$wcVariation->get_id() => 'Product already exists in Moloni.'];

                return;
            }

            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcVariation->get_id());

            $service = new CreateSimpleProduct($wcVariation);
            $service->run();

            $this->syncedProducts[] = [$wcVariation->get_id() => $service->getMoloniProduct()['reference'] ?? '---'];
        }
    }
}
