<?php

namespace MoloniES\Services\WcProduct\Create;

use WC_Product;
use MoloniES\Storage;
use MoloniES\Helpers\ProductAssociations;
use MoloniES\Services\WcProduct\Abstracts\WcProductSyncAbstract;

class CreateParentProduct extends WcProductSyncAbstract
{
    public function __construct(array $moloniProduct)
    {
        $this->moloniProduct = $moloniProduct;
        $this->wcProduct = new WC_Product();
    }

    //            Publics            //

    /**
     * Runner
     */
    public function run()
    {
        $this->setName();
        $this->setReference();
        $this->setStock();

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
        $message = sprintf(__('Parent product created in WooCommerce ({0})', 'moloni_es'), $this->wcProduct->get_sku());

        Storage::$LOGGER->info($message, [
            'moloniId' => $this->moloniProduct['productId'],
            'moloniParentId' => 0,
            'wcId' => $this->wcProduct->get_id(),
            'wcParentId' => 0
        ]);
    }

    //            Privates            //

    protected function setStock()
    {
        /** Stock is managed in variations level */
        $this->wcProduct->set_manage_stock(false);
    }

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