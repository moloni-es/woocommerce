<?php

namespace MoloniES\Services\MoloniProduct\Page;

use MoloniES\API\Companies;
use MoloniES\API\Products;
use MoloniES\Exceptions\APIExeption;

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
     * @throws APIExeption
     */
    public function run()
    {
        $this
            ->loadCompany()
            ->loadWarehouse()
            ->fetchProducts();

        if (!array($this->products)) {
            $this->products = [];
        }

        foreach ($this->products as $product) {
            if (!isset($product['productId']) || !isset($product['reference'])) {
                continue;
            }

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

    private function loadWarehouse(): FetchAndCheckProducts
    {
        $this->warehouseId = defined('HOOK_STOCK_SYNC_WAREHOUSE') ? (int)HOOK_STOCK_SYNC_WAREHOUSE : 1;

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

    //            Requests            //

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    //            Sets            //

    /**
     * Fetch products from Moloni
     */
    private function fetchProducts()
    {
        $props = [
            'options' => [
                'order' => [
                    'field' => 'reference',
                    'sort' => 'ASC',
                ],
                'pagination' => [
                    'page' => $this->page,
                    'qty' => self::$perPage,
                ],
                'filter' => [],
                'search' => null,
            ]
        ];

        if (!empty($this->filters['filter_reference'])) {
            $props['options']['filter'][] = [
                'field' => 'reference',
                'comparison' => 'like',
                'value' => '%' . $this->filters['filter_reference'] . '%'
            ];
        }

        if (!empty($this->filters['filter_name'])) {
            $props['options']['search'] = [
                'field' => 'name',
                'value' => $this->filters['filter_name']
            ];
        }

        try {
            $query = Products::queryProducts($props);
        } catch (APIExeption $e) {
            return;
        }

        $this->totalProducts = (int)($query['data']['products']['options']['pagination']['count'] ?? 0);
        $this->products = $query['data']['products']['data'] ?? [];
    }
}
