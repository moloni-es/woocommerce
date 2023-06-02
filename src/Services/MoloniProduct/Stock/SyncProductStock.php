<?php

namespace MoloniES\Services\MoloniProduct\Stock;

use MoloniES\API\Stocks;
use MoloniES\API\Warehouses;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\ServiceException;
use MoloniES\Services\MoloniProduct\Abstracts\MoloniStockSyncAbstract;
use MoloniES\Storage;
use WC_Product;

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
     * @throws APIExeption
     */
    public function run()
    {
        $wcStock = (int)$this->wcProduct->get_stock_quantity();
        $warehouseId = defined('MOLONI_STOCK_SYNC_WAREHOUSE') ? (int)MOLONI_STOCK_SYNC_WAREHOUSE : 0;

        if (in_array($warehouseId, [0, 1])) {
            $params = [
                'options' => [
                    'filter' => [
                        'field' => 'isDefault',
                        'comparison' => 'eq',
                        'value' => '1',
                    ],
                ],
            ];

            try {
                $query = Warehouses::queryWarehouses($params);

                if (!empty($query)) {
                    $warehouseId = (int)$query[0]['warehouseId'];
                }
            } catch (APIExeption $e) {
                throw new ServiceException(__('Error fetching default company warehouse', 'moloni_es'));
            }

            if (in_array($warehouseId, [0, 1])) {
                throw new ServiceException(__('Company does not have a default warehouse, please select one', 'moloni_es'));
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
                $wcStock,
                $moloniStock,
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

                $mutation = Stocks::mutationStockMovementManualExitCreate(['data' => $props]);
                $movementId = $mutation['data']['stockMovementManualExitCreate']['data']['stockMovementId'] ?? 0;
            } else {
                $diference = $wcStock - $moloniStock;

                $props['qty'] = $diference;

                $mutation = Stocks::mutationStockMovementManualEntryCreate(['data' => $props]);
                $movementId = $mutation['data']['stockMovementManualEntryCreate']['data']['stockMovementId'] ?? 0;
            }

            if (empty($movementId)) {
                throw new ServiceException(sprintf(
                    __('Something went wrong updating stock (%s)', 'moloni_es'),
                    $this->moloniProduct['reference']
                ), [
                    'mutation' => $mutation
                ]);
            }
        }

        $this->resultMsg = $msg;
        $this->resultData = [
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
