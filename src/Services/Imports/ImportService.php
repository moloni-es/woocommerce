<?php

namespace MoloniES\Services\Imports;

use WC_Product;
use MoloniES\Traits\SettingsTrait;
use MoloniES\Tools\ProductAssociations;

abstract class ImportService
{
    use SettingsTrait;

    /**
     * @var array
     */
    protected $syncedProducts = [];

    /**
     * @var array
     */
    protected $errorProducts = [];

    /**
     * @var int
     */
    protected $totalResults = 0;

    /**
     * @var int
     */
    protected $page;

    /**
     * @var int
     */
    protected $itemsPerPage = 20;

    public function __construct(?int $page = 1)
    {
        $this->page = $page;
    }

    //              Gets              //

    public function getCurrentPercentage(): int
    {
        if ($this->totalResults === 0) {
            return 100;
        }

        $percentage = (($this->page * $this->itemsPerPage) / $this->totalResults) * 100;

        return (int)$percentage;
    }

    public function getHasMore(): bool
    {
        return $this->totalResults > ($this->page * $this->itemsPerPage);
    }

    public function getTotalResults(): int
    {
        return $this->totalResults;
    }

    public function getErrorProducts(): array
    {
        return $this->errorProducts;
    }

    public function getSyncedProducts(): array
    {
        return $this->syncedProducts;
    }

    //              Privates              //

    protected function fetchWcProduct(array $moloniProduct): ?WC_Product
    {
        /** Fetch by our associaitons table */

        $association = ProductAssociations::findByMoloniId($moloniProduct['productId']);

        if (!empty($association)) {
            $wcProduct = wc_get_product($association['wc_product_id']);

            if (!empty($wcProduct)) {
                return $wcProduct;
            }

            ProductAssociations::deleteById($association['id']);
        }

        /** Fetch by reference */

        $wcProductId = wc_get_product_id_by_sku($moloniProduct['reference']);

        if ($wcProductId > 0) {
            return wc_get_product($wcProductId);
        }

        return null;
    }

    //              Abstracts              //

    abstract public function run();
}
