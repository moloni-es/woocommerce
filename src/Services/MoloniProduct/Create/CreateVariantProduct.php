<?php

namespace MoloniES\Services\MoloniProduct\Create;

use WC_Product;
use MoloniES\Helpers\ProductAssociations;
use MoloniES\Services\MoloniProduct\Abstracts\MoloniProductSyncAbstract;

class CreateVariantProduct extends MoloniProductSyncAbstract
{
    public function __construct(WC_Product $wcProduct)
    {
        $this->wcProduct = $wcProduct;
    }

    //            Publics            //

    public function run()
    {

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