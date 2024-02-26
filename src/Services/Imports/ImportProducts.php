<?php

namespace MoloniES\Services\Imports;

use Exception;
use MoloniES\API\Products;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\SyncLogsType;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Helpers\References;
use MoloniES\Services\WcProduct\Create\CreateChildProduct;
use MoloniES\Services\WcProduct\Create\CreateParentProduct;
use MoloniES\Services\WcProduct\Create\CreateSimpleProduct;
use MoloniES\Storage;
use MoloniES\Tools\SyncLogs;

class ImportProducts extends ImportService
{
    public function run(): void
    {
        $props = [
            'options' => [
                'order' => [
                    'field' => 'reference',
                    'sort' => 'DESC',
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

            if (!empty($wcProduct)) {
                $this->errorProducts[] = [$product['reference'] => 'Product already exists in WooCommerce'];

                continue;
            }

            try {
                if (empty($product['variants'])) {
                    $this->createProductSimple($product);
                } else {
                    $this->createProductWithVariations($product);
                }
            } catch (Exception $exception) {
                $this->errorProducts[] = [$product['reference'] => $exception->getMessage()];
            }
        }

        Storage::$LOGGER->info(sprintf(__('Products import. Part %s', 'moloni_es'), $this->page), [
                'tag' => 'tool:import:product',
                'success' => $this->syncedProducts,
                'error' => $this->errorProducts,
                'settings' => [
                    'syncProductWithVariations' => $this->isSyncProductWithVariantsActive()
                ]
            ]
        );
    }

    //              Privates              //

    private function createProductSimple(array $product)
    {
        SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $product['productId']);

        $service = new CreateSimpleProduct($product);
        $service->run();

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $service->getWcProduct()->get_id());

        $this->syncedProducts[] = $product['reference'];
    }

    private function createProductWithVariations(array $product)
    {
        SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $product['productId']);

        $service = new CreateParentProduct($product);
        $service->run();

        $wcParentProduct = $service->getWcProduct();

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcParentProduct->get_id());

        foreach ($product['variants'] as $variant) {
            if ((int)$variant['visible'] === Boolean::NO) {
                continue;
            }

            $service = new CreateChildProduct($variant, $wcParentProduct);
            $service->run();
        }

        $this->syncedProducts[] = $product['reference'];
    }
}
