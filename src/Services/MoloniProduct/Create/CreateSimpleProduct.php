<?php

namespace MoloniES\Services\MoloniProduct\Create;

use WC_Product;
use MoloniES\Storage;
use MoloniES\Helpers\ProductAssociations;
use MoloniES\Services\MoloniProduct\Abstracts\MoloniProductSyncAbstract;

class CreateSimpleProduct extends MoloniProductSyncAbstract
{
    public function __construct(WC_Product $wcProduct)
    {
        $this->wcProduct = $wcProduct;
    }

    //            Publics            //

    public function run()
    {
        $this->setName();
        $this->setReference();
        $this->setPrice();
        $this->setTaxes();
        $this->setCategory();
        $this->setType();
        $this->setMeasureUnit();

        if ($this->productShouldSyncDescription()) {
            $this->setSummary();
            $this->setNotes();
        }

        if ($this->productShouldSyncEAN()) {
            $this->setEan();
        }

        if ($this->productShouldSyncStock()) {
            $this->setStock();
        }

        $this->insert();

        $this->createAssociation();

        if ($this->productShouldSyncImage()) {
            $this->uploadImage();
        }
    }

    public function saveLog()
    {
        $message = sprintf(__('Simple product created in Moloni ({0})', 'moloni_es'), $this->moloniProduct['reference']);

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
        ProductAssociations::add(
            $this->wcProduct->get_id(),
            0,
            $this->moloniProduct['productId'],
            0
        );
    }
}