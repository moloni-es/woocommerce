<?php

namespace MoloniES\Services\WcProduct\Create;

use WC_Product;
use MoloniES\Storage;
use MoloniES\Helpers\ProductAssociations;
use MoloniES\Services\WcProduct\Abstracts\WcProductSyncAbstract;

class CreateChildProduct extends WcProductSyncAbstract
{
    public function __construct(array $moloniProduct, WC_Product $wcParentProduct)
    {
        $this->moloniProduct = $moloniProduct;

        $this->wcProduct = new WC_Product();
        $this->wcProductParent = $wcParentProduct;
    }

    public function run()
    {
        $this->setName();
        $this->setReference();

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
        $message = sprintf(__('Child product created in WooCommerce ({0})', 'moloni_es'), $this->wcProduct->get_sku());

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
        ProductAssociations::add(
            $this->wcProduct->get_id(),
            $this->wcProduct->get_parent_id(),
            $this->moloniProduct['productId'],
            $this->moloniProduct['parent']['productId']
        );
    }
}