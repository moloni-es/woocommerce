<?php

namespace MoloniES\Services\MoloniProduct;

use WC_Product;

class UpdateVariantProductStock
{
    private $wcProduct;

    public function __construct(WC_Product $wcProduct)
    {
        $this->wcProduct = $wcProduct;
    }

    public function run()
    {

    }

    //            Gets            //

    //            Privates            //

}