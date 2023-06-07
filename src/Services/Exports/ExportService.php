<?php

namespace MoloniES\Services\Exports;

use MoloniES\API\Products;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Tools\ProductAssociations;
use WC_Product;

abstract class ExportService
{
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

    public function getHasMore(): bool
    {
        return $this->totalResults === $this->itemsPerPage;
    }

    public function getProcessedProducts()
    {
        return (($this->page - 1) * $this->itemsPerPage) + $this->totalResults;
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

    /**
     * Fetch Moloni product
     *
     * @param WC_Product $wcProduct
     *
     * @return array|mixed
     *
     * @throws APIExeption
     */
    protected function fetchMoloniProduct(WC_Product $wcProduct)
    {
        /** Fetch by our associations table */

        $association = ProductAssociations::findByWcId($wcProduct->get_id());

        if (!empty($association)) {
            $byId = Products::queryProduct(['productId' => (int)$association['ml_product_id']]);
            $byId = $byId['data']['product']['data'] ?? [];

            if (!empty($byId)) {
                return $byId;
            }

            ProductAssociations::deleteById($association['id']);
        }

        $wcSku = $wcProduct->get_sku();

        if (empty($wcSku)) {
            return [];
        }

        $variables = [
            'options' => [
                'filter' => [
                    [
                        'field' => 'reference',
                        'comparison' => 'eq',
                        'value' => $wcSku,
                    ],
                    [
                        'field' => 'visible',
                        'comparison' => 'gte',
                        'value' => '0',
                    ]
                ],
                "includeVariants" => true
            ]
        ];

        $byReference = Products::queryProducts($variables);

        if (!empty($byReference) && isset($byReference[0]['productId'])) {
            return $byReference[0];
        }

        return [];
    }

    //              Abstracts              //

    abstract public function run();
}
