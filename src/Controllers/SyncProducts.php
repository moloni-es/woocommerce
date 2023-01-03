<?php

namespace MoloniES\Controllers;

use Exception;
use MoloniES\API\Products;
use MoloniES\Error;
use MoloniES\Log;

class SyncProducts
{

    private $since;
    private $found = 0;
    private $updated = [];
    private $equal = [];
    private $notFound = [];

    /** @var string Switch this between outofstock or onbackorder */
    private $outOfStockStatus = 'onbackorder';

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
     * @return SyncProducts
     */
    public function run()
    {
        Log::write(sprintf(__('Syncing products since %s' , 'moloni_es') , $this->since));

        $updatedProducts = $this->getAllMoloniProducts();

        if (!empty($updatedProducts) && is_array($updatedProducts)) {
            $this->found = count($updatedProducts);
            Log::write(sprintf(__('Found %s products','moloni_es'),$this->found));
            foreach ($updatedProducts as $product) {
                try {
                    $wcProductId = wc_get_product_id_by_sku($product['reference']);
                    if ($product['hasStock'] && $wcProductId > 0 && (get_post_meta($wcProductId,'_manage_stock'))[0] !== 'no') {
                        $currentStock = get_post_meta($wcProductId, '_stock', true);
                        $newStock = $product['stock'];

                        if ((float)$currentStock === (float)$newStock) {
                            Log::write(sprintf(__('Product with reference %1$s already was up-to-date %2$s | %3$s','moloni_es'),$product['reference'],$currentStock,$newStock));
                            $this->equal[$product['reference']] = sprintf(__('Product with reference %s was already up-to-date' , 'moloni_es') , $product['reference']);
                        } else {
                            Log::write(sprintf(__('Product with reference %1$s was updated from %2$s to %3$s','moloni_es'),$product['reference'],$currentStock,$newStock));
                            $this->updated[$product['reference']] = sprintf(__('Product with reference %1$s was updated from %2$s to %3$s','moloni_es'),$product['reference'],$currentStock,$newStock);
                            wc_update_product_stock($wcProductId, $newStock);
                        }
                    } else {
                        Log::write(sprintf(__('Product not found in WooCommerce or without active stock: %s' , 'moloni_es') , $product['reference']));
                        $this->notFound[$product['reference']] = __('Product not found in WooCommerce or without active stock','moloni_es');
                    }
                } catch (Exception $error) {
                    Log::write(sprintf(__('Error: %s','moloni_es') , $error->getMessage()));
                }
            }
        } else {
            Log::write(sprintf(__('No products to update since %s','moloni_es') , $this->since));
        }

        return $this;
    }

    /**
     * Get the amount of records found
     * @return int
     */
    public function countFoundRecord()
    {
        return $this->found;
    }

    /**
     * Get the amount of records update
     * @return int
     */
    public function countUpdated()
    {
        return count($this->updated);
    }

    /**
     * Get the amount of records that had the same stock count
     * @return int
     */
    public function countEqual()
    {
        return count($this->equal);
    }

    /**
     * Get the amount of products not found in WooCommerce
     * @return int
     */
    public function countNotFound()
    {
        return count($this->notFound);
    }

    /**
     * Return the updated products
     * @return array
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Return the list of products that had the same stock as in WooCommerce
     * @return array
     */
    public function getEqual()
    {
        return $this->equal;
    }

    /**
     * Return the list of products update in Moloni but not found in WooCommerce
     * @return array
     */
    public function getNotFound()
    {
        return $this->notFound;
    }

    /**
     * Get ALL moloni Products
     * @return array
     */
    private function getAllMoloniProducts()
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
            Log::write(__('Warning, error getting products via API','moloni_es'));
        }

        if (isset($fetched[0]['productId'])) {
            $productsList = $fetched;
        }

        return $productsList;
    }

}
