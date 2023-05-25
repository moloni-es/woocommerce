<?php

namespace MoloniES\Services\MoloniProduct\Stock;

use WC_Product;
use MoloniES\Services\MoloniProduct\Abstracts\MoloniStockSyncAbstract;

class SyncProductStock extends MoloniStockSyncAbstract
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
}