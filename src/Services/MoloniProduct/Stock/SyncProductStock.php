<?php

namespace MoloniES\Services\MoloniProduct\Stock;

use WC_Product;
use MoloniES\API\Stocks;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\HelperException;
use MoloniES\Exceptions\ServiceException;
use MoloniES\Helpers\MoloniWarehouse;
use MoloniES\Services\MoloniProduct\Abstracts\MoloniStockSyncAbstract;
use MoloniES\Storage;

class SyncProductStock extends MoloniStockSyncAbstract
{
    public function __construct(WC_Product $wcProduct, array $moloniProduct)
    {
        $this->wcProduct = $wcProduct;
        $this->moloniProduct = $moloniProduct;
    }

    //            Publics            //

    /**
     * Runner
     *
     * @throws ServiceException
     */
    public function run()
    {
        $wcStock = (int)$this->wcProduct->get_stock_quantity();
        $warehouseId = defined('MOLONI_STOCK_SYNC_WAREHOUSE') ? (int)MOLONI_STOCK_SYNC_WAREHOUSE : 0;

        if (empty($warehouseId)) {
            try {
                $warehouseId = MoloniWarehouse::getDefaultWarehouseId();
            } catch (HelperException $e) {
                throw new ServiceException($e->getMessage(), $e->getData());
            }
        }

        $moloniStock = 0;

        foreach ($this->moloniProduct['warehouses'] as $warehouse) {
            if ((int)$warehouse['warehouseId'] === $warehouseId) {
                $moloniStock = (int)$warehouse['stock'];

                break;
            }
        }

        if ($wcStock === $moloniStock) {
            $msg = sprintf(
                __('Stock is already updated in Moloni (%s)', 'moloni_es'),
                $this->moloniProduct['reference']
            );
        } else {
            $msg = sprintf(
                __('Stock updated in Moloni (old: %s | new: %s) (%s)', 'moloni_es'),
                $moloniStock,
                $wcStock,
                $this->moloniProduct['reference']
            );

            $props = [
                'productId' => $this->moloniProduct['productId'],
                'notes' => 'Wordpress',
                'warehouseId' => $warehouseId,
            ];

            if ($moloniStock > $wcStock) {
                $diference = $moloniStock - $wcStock;

                $props['qty'] = $diference;

                try {
                    $mutation = Stocks::mutationStockMovementManualExitCreate(['data' => $props]);
                } catch (APIExeption $e) {
                    throw new ServiceException(
                        sprintf(
                            __('Something went wrong updating stock (%s)', 'moloni_es'),
                            $this->moloniProduct['reference']
                        ),
                        [
                            'message' => $e->getMessage(),
                            'data' => $e->getData(),
                            'props' => $props,
                        ]
                    );
                }

                $movementId = $mutation['data']['stockMovementManualExitCreate']['data']['stockMovementId'] ?? 0;
            } else {
                $diference = $wcStock - $moloniStock;

                $props['qty'] = $diference;

                try {
                    $mutation = Stocks::mutationStockMovementManualEntryCreate(['data' => $props]);
                } catch (APIExeption $e) {
                    throw new ServiceException(
                        sprintf(
                            __('Something went wrong updating stock (%s)', 'moloni_es'),
                            $this->moloniProduct['reference']
                        ),
                        [
                            'message' => $e->getMessage(),
                            'data' => $e->getData(),
                            'props' => $props,
                        ]
                    );
                }

                $movementId = $mutation['data']['stockMovementManualEntryCreate']['data']['stockMovementId'] ?? 0;
            }

            if (empty($movementId)) {
                throw new ServiceException(sprintf(
                    __('Something went wrong updating stock (%s)', 'moloni_es'),
                    $this->moloniProduct['reference']
                ), [
                    'mutation' => $mutation,
                    'props' => $props
                ]);
            }
        }

        $this->resultMsg = $msg;
        $this->resultData = [
            'tag' => 'service:mlproduct:sync:stock',
            'WooCommerceId' => $this->wcProduct->get_id(),
            'WooCommerceParentId' => $this->wcProduct->get_parent_id(),
            'WooCommerceStock' => $wcStock,
            'MoloniStock' => $moloniStock,
            'MoloniProductId' => $this->moloniProduct['productId'],
            'MoloniProductParentId' => $this->moloniProduct['parent']['productId'] ?? null,
            'MoloniReference' => $this->moloniProduct['reference'],
        ];
    }

    public function saveLog()
    {
        Storage::$LOGGER->info($this->resultMsg, $this->resultData);
    }
}
