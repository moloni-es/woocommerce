<?php

namespace MoloniES\Services\WcProduct\Abstracts;

use WC_Product;
use MoloniES\Services\WcProduct\Interfaces\WcSyncInterface;

abstract class WcStockSyncAbstract implements WcSyncInterface
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
}