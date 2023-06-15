<?php

namespace MoloniES\Services\WcProduct\Update;

use MoloniES\Services\WcProduct\Abstracts\WcProductSyncAbstract;
use MoloniES\Storage;
use MoloniES\Tools\ProductAssociations;
use WC_Product;

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
        $this->setParent();

        if ($this->productShouldSyncName()) {
            $this->setName();
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

        $this->setVariationOptions();

        $this->wcProduct->save();

        $this->createAssociation();
    }

    public function saveLog()
    {
        $message = sprintf(__('Variation product updated in WooCommerce (%s)', 'moloni_es'), $this->wcProduct->get_sku());

        Storage::$LOGGER->info($message, [
            'tag' => 'service:wcproduct:child:update',
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
