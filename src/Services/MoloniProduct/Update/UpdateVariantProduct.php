<?php

namespace MoloniES\Services\MoloniProduct\Update;

use WC_Product;
use MoloniES\Helpers\ProductAssociations;
use MoloniES\Services\MoloniProduct\Abstracts\MoloniProductSyncAbstract;

class UpdateVariantProduct extends MoloniProductSyncAbstract
{
    public function __construct(WC_Product $wcProduct, array $moloniProduct)
    {
        $this->wcProduct = $wcProduct;
        $this->moloniProduct = $moloniProduct;
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