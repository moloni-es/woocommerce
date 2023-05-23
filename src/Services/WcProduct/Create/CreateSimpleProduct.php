<?php

namespace MoloniES\Services\WcProduct\Create;

use MoloniES\Enums\Boolean;
use MoloniES\Helpers\MoloniProduct;
use MoloniES\Helpers\ProductAssociations;
use MoloniES\Services\WcProduct\Abstracts\WcProductSyncAbstract;
use MoloniES\Services\WcProduct\Helpers\FetchImageFromMoloni;
use MoloniES\Services\WcProduct\Helpers\FetchWcCategoriesFromMoloniCategoryId;
use MoloniES\Storage;
use WC_Product;

class CreateSimpleProduct extends WcProductSyncAbstract
{
    private $wcProduct;
    private $moloniProduct;

    public function __construct(array $moloniProduct)
    {
        $this->moloniProduct = $moloniProduct;
        $this->wcProduct = new WC_Product();
    }

    public function run()
    {
        $this->wcProduct->set_name($this->moloniProduct['name'] ?? '');
        $this->wcProduct->set_sku($this->moloniProduct['reference'] ?? '');

        if (wc_prices_include_tax()) {
            $this->wcProduct->set_regular_price($this->moloniProduct['priceWithTaxes']);
        } else {
            $this->wcProduct->set_regular_price($this->moloniProduct['price']);
        }

        if (empty($this->moloniProduct['taxes'])) {
            $this->wcProduct->set_tax_status('none');
        } else {
            $this->wcProduct->set_tax_status('taxable');
        }

        $categoryIds = (new FetchWcCategoriesFromMoloniCategoryId($this->moloniProduct['productCategory']['productCategoryId'] ?? 0))->get();

        if (!empty($categoryIds)) {
            $this->wcProduct->set_category_ids($categoryIds);
        }

        if ($this->productShouldSyncDescription()) {
            $this->wcProduct->set_short_description($this->moloniProduct['summary'] ?? '');
            $this->wcProduct->set_description($this->moloniProduct['notes'] ?? '');
        }

        if ($this->productShouldSyncVisibility()) {
            $this->wcProduct->set_catalog_visibility((int)$this->moloniProduct['visible'] === Boolean::YES ? 'visible' : 'hidden');
        }

        if ($this->productShouldSyncStock()) {
            $hasStock = (bool)$this->moloniProduct['hasStock'];

            $this->wcProduct->set_manage_stock($hasStock);

            if ($hasStock) {
                $stock = MoloniProduct::parseMoloniStock(
                    $this->moloniProduct,
                    defined('HOOK_STOCK_SYNC_WAREHOUSE') ? (int)HOOK_STOCK_SYNC_WAREHOUSE : 1
                );

                $this->wcProduct->set_stock_quantity($stock);
                $this->wcProduct->set_low_stock_amount($this->moloniProduct['minStock']);
            }
        }

        if ($this->productShouldSyncEAN()) {
            foreach ($this->moloniProduct['identifications'] as $identification) {
                if ($identification['type'] === 'EAN13') {
                    if (!$this->wcProduct->get_meta('_barcode')) {
                        $this->wcProduct->add_meta_data('_barcode', $identification['text']);
                    } else {
                        $this->wcProduct->update_meta_data('_barcode', $identification['text']);
                    }

                    break;
                }
            }
        }

        if ($this->productShouldSyncImage()) {
            if (empty($this->moloniProduct['img'])) {
                $this->wcProduct->set_image_id('');
            } else {
                $imageId = (new FetchImageFromMoloni($this->moloniProduct['img']))->get();

                if ($imageId > 0) {
                    $this->wcProduct->set_image_id($imageId);
                }
            }
        }

        $this->wcProduct->save();

        $this->createAssociation();
    }

    public function saveLog()
    {
        $message = sprintf(__('Simple product created in WooCommerce ({0})', 'moloni_es'), $this->wcProduct->get_sku());

        Storage::$LOGGER->info($message, [
            'moloniId' => $this->moloniProduct['productId'],
            'moloniParentId' => 0,
            'wcId' => $this->wcProduct->get_id(),
            'wcParentId' => 0
        ]);
    }

    //            Gets            //

    public function getWcProduct(): WC_Product
    {
        return $this->wcProduct;
    }

    public function getMoloniProduct(): array
    {
        return $this->moloniProduct;
    }

    //            Private            //

    //            Auxliary            //

    protected function createAssociation()
    {
        ProductAssociations::add(
            $this->wcProduct->get_id(),
            0,
            $this->moloniProduct['productId'],
            0
        );
    }
}