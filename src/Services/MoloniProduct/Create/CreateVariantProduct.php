<?php

namespace MoloniES\Services\MoloniProduct\Create;

use MoloniES\Services\MoloniProduct\Abstracts\MoloniProductSyncAbstract;
use MoloniES\Tools\ProductAssociations;
use WC_Product;

class CreateVariantProduct extends MoloniProductSyncAbstract
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

        $this->setVariants();

        $this->insert();

        $this->createAssociation();

        if ($this->productShouldSyncImage()) {
            $this->uploadImage();
        }
    }

    public function saveLog()
    {
        // TODO: Implement saveLog() method.
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