<?php

namespace MoloniES\Services\WcProduct\Page;

use MoloniES\API\Companies;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\HelperException;
use MoloniES\Helpers\MoloniWarehouse;

class FetchAndCheckProducts
{
    private static $perPage = 20;

    private $page = 1;
    private $filters = [];

    private $rows = [];

    private $products = [];
    private $totalProducts = 0;

    private $warehouseId = 0;
    private $company = [];

    //            Public's            //

    /**
     * Service runner
     *
     * @return void
     *
     * @throws HelperException
     * @throws APIExeption
     */
    public function run()
    {
        $this
            ->loadCompany()
            ->loadWarehouse()
            ->fetchProducts();

        foreach ($this->products as $product) {
            $service = new CheckProduct($product, $this->warehouseId, $this->company);
            $service->run();

            $this->rows[] = $service->getRowsHtml();
        }
    }

    public function getPaginator()
    {
        $baseArguments = add_query_arg([
            'paged' => '%#%',
            'filter_name' => $this->filters['filter_name'],
            'filter_reference' => $this->filters['filter_reference'],
        ]);

        $args = [
            'base' => $baseArguments,
            'format' => '',
            'current' => $this->page,
            'total' => ceil($this->totalProducts / self::$perPage),
        ];

        return paginate_links($args);
    }

    //            Privates            //

    /**
     * Load company
     *
     * @throws APIExeption
     */
    private function loadCompany(): FetchAndCheckProducts
    {
        $company = Companies::queryCompany();

        $this->company = $company['data']['company']['data'];

        return $this;
    }

    /**
     * Load warehouse to use
     *
     * @throws HelperException
     */
    private function loadWarehouse(): FetchAndCheckProducts
    {
        $warehouseId = defined('MOLONI_STOCK_SYNC_WAREHOUSE') ? (int)MOLONI_STOCK_SYNC_WAREHOUSE : 0;

        if (empty($warehouseId)) {
            $warehouseId = MoloniWarehouse::getDefaultWarehouseId();
        }

        $this->warehouseId = $warehouseId;

        return $this;
    }

    //            Gets            //

    public function getProducts(): array
    {
        return $this->products;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getTotalProducts(): int
    {
        return $this->totalProducts;
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    public function getWarehouseId(): int
    {
        return $this->warehouseId;
    }

    public function getCompany(): array
    {
        return $this->company;
    }

    //            Sets            //

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    //            Requests            //

    /**
     * Fetch products from WooCommerce
     */
    private function fetchProducts()
    {
        /**
         * @see https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
         */
        $filters = [
            'status' => ['publish'],
            'limit' => self::$perPage,
            'page' => $this->page,
            'paginate' => true,
            'orderby' => [
                'ID' => 'DESC',
            ],
        ];

        if (!empty($this->filters['filter_reference'])) {
            $filters['sku'] = $this->filters['filter_reference'];
        }

        if (!empty($this->filters['filter_name'])) {
            $filters['name'] = $this->filters['filter_name'];
        }

        $query = wc_get_products($filters);

        $this->products = $query->products ?? [];
        $this->totalProducts = (int)($query->total ?? 0);
    }
}
