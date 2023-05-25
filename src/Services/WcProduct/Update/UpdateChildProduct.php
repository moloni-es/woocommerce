<?php

namespace MoloniES\Services\WcProduct\Update;

use WC_Product;
use MoloniES\Storage;
use MoloniES\Helpers\ProductAssociations;
use MoloniES\Services\WcProduct\Abstracts\WcProductSyncAbstract;

class UpdateChildProduct extends WcProductSyncAbstract
{
    public function __construct(array $moloniProduct, WC_Product $wcProduct, WC_Product $wcProductParent)
    {
        $this->moloniProduct = $moloniProduct;

        $this->wcProduct = $wcProduct;
        $this->wcProductParent = $wcProductParent;
    }

    public function run()
    {
        if ($this->productShouldSyncName()) {
            $this->setName();
        }

        if ($this->productShouldSyncDescription()) {
            $this->setReference();
        }

        if ($this->productShouldSyncDescription()) {
            $this->setDescripton();
        }

        if ($this->productShouldSyncPrice()) {
            $this->setPrice();
        }

        if ($this->productShouldSyncStock()) {
            $this->setStock();
        }

        if ($this->productShouldSyncImage()) {
            $this->setImage();
        }

        $this->setAttributesOptions();
        $this->setParent();

        $this->wcProduct->save();

        $this->createAssociation();
    }

    public function saveLog()
    {
        $message = sprintf(__('Child product updated in WooCommerce (%s)', 'moloni_es'), $this->wcProduct->get_sku());

        Storage::$LOGGER->info($message, [
            'moloniId' => $this->moloniProduct['productId'],
            'moloniParentId' => $this->moloniProduct['parent']['productId'],
            'wcId' => $this->wcProduct->get_id(),
            'wcParentId' => $this->wcProduct->get_parent_id(),
        ]);
    }

    //            Auxliary            //

    protected function createAssociation()
    {
        ProductAssociations::deleteByWcId($this->wcProduct->get_id());
        ProductAssociations::deleteByMoloniId($this->moloniProduct['productId']);

        ProductAssociations::add(
            $this->wcProduct->get_id(),
            $this->wcProduct->get_parent_id(),
            $this->moloniProduct['productId'],
            $this->moloniProduct['parent']['productId']
        );
    }
}