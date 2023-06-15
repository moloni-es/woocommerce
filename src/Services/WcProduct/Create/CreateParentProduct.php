<?php

namespace MoloniES\Services\WcProduct\Create;

use MoloniES\Services\WcProduct\Abstracts\WcProductSyncAbstract;
use MoloniES\Storage;
use MoloniES\Tools\ProductAssociations;
use WC_Product_Variable;

class CreateParentProduct extends WcProductSyncAbstract
{
    public function __construct(array $moloniProduct)
    {
        $this->moloniProduct = $moloniProduct;
        $this->wcProduct = new WC_Product_Variable();
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
        $message = sprintf(__('Product with variations created in WooCommerce (%s)', 'moloni_es'), $this->wcProduct->get_sku());

        Storage::$LOGGER->info($message, [
            'tag' => 'service:wcproduct:parent:create',
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
