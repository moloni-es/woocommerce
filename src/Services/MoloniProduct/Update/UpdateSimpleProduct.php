<?php

namespace MoloniES\Services\MoloniProduct\Update;

use MoloniES\Storage;
use WC_Product;
use MoloniES\Helpers\ProductAssociations;
use MoloniES\Services\MoloniProduct\Abstracts\MoloniProductSyncAbstract;

class UpdateSimpleProduct extends MoloniProductSyncAbstract
{
    public function __construct(WC_Product $wcProduct, array $moloniProduct)
    {
        $this->wcProduct = $wcProduct;
        $this->moloniProduct = $moloniProduct;
    }

    //            Publics            //

    public function run()
    {
        $this->setProductId();

        if ($this->productShouldSyncName()) {
            $this->setName();
        }

        if ($this->productShouldSyncPrice()) {
            $this->setPrice();
            $this->setTaxes();
        }

        if ($this->productShouldSyncCategories()) {
            $this->setCategory();
        }

        if ($this->productShouldSyncDescription()) {
            $this->setSummary();
            $this->setNotes();
        }

        if ($this->productShouldSyncEAN()) {
            $this->setEan();
        }

        $this->update();

        $this->createAssociation();

        if ($this->productShouldSyncImage()) {
            $this->uploadImage();
        }
    }

    public function saveLog()
    {
        $message = sprintf(__('Simple product updated in Moloni ({0})', 'moloni_es'), $this->moloniProduct['reference']);

        Storage::$LOGGER->info($message, [
            'moloniId' => $this->moloniProduct['productId'],
            'moloniParentId' => 0,
            'wcId' => $this->wcProduct->get_id(),
            'wcParentId' => 0
        ]);
    }

    //            Privates            //

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