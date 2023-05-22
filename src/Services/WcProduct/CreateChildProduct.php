<?php

namespace MoloniES\Services\WcProduct;

use WC_Product;
use MoloniES\Services\WcProduct\Abstracts\WcProductServiceAbstract;

class CreateChildProduct extends WcProductServiceAbstract
{
    private $moloniProduct;
    private $wooProduct;

    public function __construct(array $moloniProduct)
    {
        $this->moloniProduct = $moloniProduct;
        $this->wooProduct = new WC_Product();
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