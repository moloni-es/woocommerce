<?php

namespace MoloniES\Services\WcProduct\Update;

use MoloniES\Enums\Boolean;
use MoloniES\Helpers\MoloniProduct;
use MoloniES\Helpers\ProductAssociations;
use MoloniES\Services\WcProduct\Abstracts\WcProductSyncAbstract;
use MoloniES\Services\WcProduct\Helpers\FetchImageFromMoloni;
use MoloniES\Services\WcProduct\Helpers\FetchWcCategoriesFromMoloniCategoryId;
use MoloniES\Storage;
use WC_Product;
use WC_Product_Attribute;

class UpdateParentProduct extends WcProductSyncAbstract
{
    public function __construct(array $moloniProduct, WC_Product $wcProduct)
    {
        $this->moloniProduct = $moloniProduct;
        $this->wcProduct = $wcProduct;
    }

    public function run()
    {
        if ($this->productShouldSyncName()) {
            $this->setName();
        }

        if ($this->productShouldSyncPrice()) {
            $this->setTaxes();
        }

        if ($this->productShouldSyncCategories()) {
            $this->setCategories();
        }

        if ($this->productShouldSyncDescription()) {
            $this->setDescripton();
        }

        if ($this->productShouldSyncVisibility()) {
            $this->setVisibility();
        }

        if ($this->productShouldSyncEAN()) {
            $this->setEan();
        }

        if ($this->productShouldSyncImage()) {
            $this->setImage();
        }

        $this->setAttributes();

        $this->wcProduct->save();

        $this->createAssociation();
    }

    public function saveLog()
    {
        $message = sprintf(__('Parent product updated in WooCommerce ({0})', 'moloni_es'), $this->wcProduct->get_sku());

        Storage::$LOGGER->info($message, [
            'moloniId' => $this->moloniProduct['productId'],
            'moloniParentId' => 0,
            'wcId' => $this->wcProduct->get_id(),
            'wcParentId' => 0
        ]);
    }

    //            Auxliary            //

    protected function createAssociation()
    {
        ProductAssociations::deleteByWcId($this->wcProduct->get_id());
        ProductAssociations::deleteByMoloniId($this->moloniProduct['productId']);

        ProductAssociations::add(
            $this->wcProduct->get_id(),
            0,
            $this->moloniProduct['productId'],
            0
        );
    }
}