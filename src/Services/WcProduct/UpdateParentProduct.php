<?php

namespace MoloniES\Services\WcProduct;

use WC_Product;
use MoloniES\Services\WcProduct\Abstracts\WcProductServiceAbstract;

class UpdateParentProduct extends WcProductServiceAbstract
{
    private $moloniProduct;
    private $wooProduct;

    public function __construct(array $moloniProduct, WC_Product $wooProduct)
    {
        $this->moloniProduct = $moloniProduct;
        $this->wooProduct = $wooProduct;
    }

    public function run()
    {

    }

    public function saveLog()
    {
        // TODO: Implement saveLog() method.
    }

    //            Gets            //

    //            Privates            //
}