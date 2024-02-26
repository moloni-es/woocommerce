<?php

namespace MoloniES\Services\MoloniProduct\Abstracts;

use WC_Product;
use MoloniES\Services\MoloniProduct\Interfaces\MoloniProductServiceInterface;

abstract class MoloniStockSyncAbstract implements MoloniProductServiceInterface
{
    /**
     * WooCommerce product
     *
     * @var WC_Product|null
     */
    protected $moloniProduct;

    /**
     * Moloni Product
     *
     * @var array|null
     */
    protected $wcProduct;

    /**
     * Result message
     *
     * @var string
     */
    protected $resultMsg = '';

    /**
     * Result data
     *
     * @var array
     */
    protected $resultData = [];

    //            Gets            //

    public function getResultMsg(): string
    {
        return $this->resultMsg;
    }

    public function getResultData(): array
    {
        return $this->resultData;
    }
}
