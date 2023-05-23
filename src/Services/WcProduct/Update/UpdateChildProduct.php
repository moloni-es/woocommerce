<?php

namespace MoloniES\Services\WcProduct\Update;

use MoloniES\Helpers\MoloniProduct;
use MoloniES\Helpers\ProductAssociations;
use MoloniES\Services\WcProduct\Abstracts\WcProductSyncAbstract;
use MoloniES\Services\WcProduct\Helpers\FetchImageFromMoloni;
use MoloniES\Storage;
use WC_Product;

class UpdateChildProduct extends WcProductSyncAbstract
{
    private $moloniVariant;
    private $wcProduct;
    private $wcParentProduct;

    public function __construct(array $moloniVariant, WC_Product $wcProduct, WC_Product $wcParentProduct)
    {
        $this->moloniVariant = $moloniVariant;

        $this->wcProduct = $wcProduct;
        $this->wcParentProduct = $wcParentProduct;
    }

    public function run()
    {
        $this->wcProduct->set_name($this->moloniVariant['name'] ?? '');
        $this->wcProduct->set_sku($this->moloniVariant['reference'] ?? '');

        if ($this->productShouldSyncDescription()) {
            $this->wcProduct->set_short_description($this->moloniVariant['summary'] ?? '');
            $this->wcProduct->set_description($this->moloniVariant['notes'] ?? '');
        }

        if (wc_prices_include_tax()) {
            $this->wcProduct->set_regular_price($this->moloniVariant['priceWithTaxes']);
        } else {
            $this->wcProduct->set_regular_price($this->moloniVariant['price']);
        }

        if ($this->productShouldSyncStock()) {
            $hasStock = (bool)$this->moloniVariant['hasStock'];

            $this->wcProduct->set_manage_stock($hasStock);

            if ($hasStock) {
                $stock = MoloniProduct::parseMoloniStock(
                    $this->moloniVariant,
                    defined('HOOK_STOCK_SYNC_WAREHOUSE') ? (int)HOOK_STOCK_SYNC_WAREHOUSE : 1
                );

                $this->wcProduct->set_stock_quantity($stock);
                $this->wcProduct->set_low_stock_amount($this->moloniVariant['minStock']);
            }
        }

        if ($this->productShouldSyncImage()) {
            if (empty($this->moloniVariant['img'])) {
                $this->wcProduct->set_image_id('');
            } else {
                $imageId = (new FetchImageFromMoloni($this->moloniVariant['img']))->get();

                if ($imageId > 0) {
                    $this->wcProduct->set_image_id($imageId);
                }
            }
        }

        $this->setAttributes();

        $this->wcProduct->set_parent_id($this->wcParentProduct->get_id());
        $this->wcProduct->save();

        $this->createAssociation();
    }

    public function saveLog()
    {
        $message = sprintf(__('Child product updated in WooCommerce ({0})', 'moloni_es'), $this->wcProduct->get_sku());

        Storage::$LOGGER->info($message, [
            'moloniId' => $this->moloniVariant['productId'],
            'moloniParentId' => $this->moloniVariant['parent']['productId'],
            'wcId' => $this->wcProduct->get_id(),
            'wcParentId' => $this->wcProduct->get_parent_id(),
        ]);
    }

    //            Gets            //

    public function getWcProduct(): WC_Product
    {
        return $this->wcProduct;
    }

    public function getMoloniVariant(): array
    {
        return $this->moloniVariant;
    }

    //            Privates            //

    private function setAttributes()
    {
        $attributes = MoloniProduct::parseVariantAttributes($this->moloniVariant);

        $this->wcProduct->set_attributes($attributes);
    }

    //            Auxliary            //

    protected function createAssociation()
    {
        ProductAssociations::add(
            $this->wcProduct->get_id(),
            $this->wcProduct->get_parent_id(),
            $this->moloniVariant['productId'],
            $this->moloniVariant['parent']['productId']
        );
    }
}