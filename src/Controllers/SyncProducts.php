<?php

namespace MoloniES\Controllers;

use Exception;
use MoloniES\API\Products;
use MoloniES\Exceptions\Error;
use MoloniES\Log;
use MoloniES\Storage;

class SyncProducts
{

    private $since;
    private $found = 0;
    private $updated = [];
    private $equal = [];
    private $notFound = [];

    /**
     * SyncProducts constructor.
     * @param $since
     */
    public function __construct($since)
    {
        if (is_numeric($since)) {
            $sinceTime = $since;
        } else {
            $sinceTime = strtotime($since);
            if (!$sinceTime) {
                $sinceTime = strtotime('-1 week');
            }
        }

        $this->since = gmdate('Y-m-d H:i:s', $sinceTime);
    }

    /**
     * Run the sync operation
     *
     * @return SyncProducts
     */
    public function run(): SyncProducts
    {
        $updatedProducts = $this->getAllMoloniProducts();

        if (!empty($updatedProducts)) {
            $this->found = count($updatedProducts);

            foreach ($updatedProducts as $product) {
                $moloniReference = $product['reference'];

                try {
                    $wcProductId = wc_get_product_id_by_sku($moloniReference);
                    $wcProduct = wc_get_product($wcProductId);

                    if ($product['hasStock'] && $wcProduct && $wcProduct->managing_stock()) {
                        $currentStock = $wcProduct->get_stock_quantity();
                        $newStock = $product['stock'];

                        if ((float)$currentStock === (float)$newStock) {
                            $this->equal[$moloniReference] = sprintf(
                                __('Product was already was up-to-date %s | %s (%s)', 'moloni_es'),
                                $currentStock,
                                $newStock,
                                $moloniReference
                            );
                        } else {
                            $this->updated[$moloniReference] = sprintf(
                                __('Product was updated from %s to %s (%s)', 'moloni_es'),
                                $currentStock,
                                $newStock,
                                $moloniReference
                            );

                            wc_update_product_stock($wcProduct, $newStock);
                        }
                    } else {
                        $this->notFound[$moloniReference] = sprintf(
                            __('Product not found in WooCommerce or without active stock (%s)', 'moloni_es'),
                            $moloniReference
                        );
                    }
                } catch (Exception $error) {
                    Storage::$LOGGER->critical(__('Fatal error'), [
                        'action' => 'stock:sync:service',
                        'exception' => $error->getMessage()
                    ]);
                }
            }
        }

        return $this;
    }

    /**
     * Get the amount of records found
     *
     * @return int
     */
    public function countFoundRecord(): int
    {
        return $this->found;
    }

    /**
     * Get the amount of records updates
     *
     * @return int
     */
    public function countUpdated(): int
    {
        return count($this->updated);
    }

    /**
     * Get the amount of records that had the same stock count
     *
     * @return int
     */
    public function countEqual(): int
    {
        return count($this->equal);
    }

    /**
     * Get the amount of products not found in WooCommerce
     *
     * @return int
     */
    public function countNotFound(): int
    {
        return count($this->notFound);
    }

    /**
     * Return the updated products
     *
     * @return array
     */
    public function getUpdated(): array
    {
        return $this->updated;
    }

    /**
     * Return the list of products that had the same stock as in WooCommerce
     *
     * @return array
     */
    public function getEqual(): array
    {
        return $this->equal;
    }

    /**
     * Return the list of products update in Moloni but not found in WooCommerce
     *
     * @return array
     */
    public function getNotFound(): array
    {
        return $this->notFound;
    }

    /**
     * Get date used to fetch
     *
     * @return false|string
     */
    public function getSince()
    {
        return $this->since ?? '';
    }

    /**
     * Get ALL moloni Products
     *
     * @return array
     */
    private function getAllMoloniProducts(): array
    {
        $productsList = [];

        $variables = [
            'options' => [
                'filter' => [
                    [
                        'field' => 'updatedAt',
                        'comparison' => 'gte',
                        'value' => $this->since
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

        try {
            $fetched = Products::queryProducts($variables);
        } catch (Error $e) {
            $fetched = [];

            Storage::$LOGGER->critical(__('Warning, error getting products via API', 'moloni_es'), [
                'action' => 'stock:sync:service',
                'message' => $e->getMessage(),
                'exception' => $e->getRequest(),
            ]);
        }

        if (isset($fetched[0]['productId'])) {
            $productsList = $fetched;
        }

        return $productsList;
    }
}
