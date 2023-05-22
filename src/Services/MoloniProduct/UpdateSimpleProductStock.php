<?php

namespace MoloniES\Services\MoloniProduct;

use WC_Product;

class UpdateSimpleProductStock
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