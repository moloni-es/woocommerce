<?php

namespace MoloniES\Services\MoloniProduct\Variant;

use MoloniES\API\Warehouses;
use MoloniES\Enums\Boolean;
use MoloniES\Services\MoloniProduct\Helpers\Variants\FindVariant;
use MoloniES\Tools\ProductAssociations;
use MoloniES\Traits\SyncFieldsSettingsTrait;
use WC_Product;
use WC_Product_Variation;

class MoloniVariant
{
    use SyncFieldsSettingsTrait;

    /**
     * WooCommerce product
     *
     * @var WC_Product_Variation|null
     */
    private $wcProduct;

    /**
     * WooCommerce product parsed property pairs
     *
     * @var array
     */
    private $propertyPairs;

    /**
     * Moloni product
     *
     * @var array
     */
    private $moloniVariant = [];

    /**
     * Moloni parent product
     *
     * @var array
     */
    private $moloniParentProduct = [];


    /**
     * Create props
     *
     * @var array
     */
    private $props = [];

    public function __construct($wcProduct, ?array $moloniParentProduct = [], ?array $propertyPairs = [])
    {
        $this->wcProduct = $wcProduct;
        $this->moloniParentProduct = $moloniParentProduct;
        $this->propertyPairs = $propertyPairs;
    }

    //            Publics            //

    public function findVariant()
    {
        if ($this->variantExists()) {
            return;
        }

        if (!$this->parentProductExists()) {
            return;
        }

        $variant = (new FindVariant(
            $this->wcProduct->get_id(),
            $this->wcProduct->get_sku() ?? '',
            $this->moloniParentProduct['variants'] ?? [],
            $this->propertyPairs ?? []
        ))->run();

        if (!empty($variant)) {
            $this->moloniVariant = $variant;
        }
    }

    public function run()
    {
        $this->setVisibility();

        if ($this->variantExists()) {
            $this->setProductId();

            if ($this->productShouldSyncName()) {
                $this->setName();
            }

            if ($this->productShouldSyncPrice()) {
                $this->setPrice();
            }
        } else {
            $this->setName();
            $this->setPrice();
            $this->setPropertyPairs();

            if ($this->productShouldSyncStock())  {
                $this->setStock();
            }
        }

        if ($this->productShouldSyncDescription()) {
            $this->setSummary();
            $this->setNotes();
        }
    }

    //            Privates            //

    public function createAssociation()
    {
        // todo: remove after testing
        /*  if (!$this->variantExists()) {
            return;
        }*/

        ProductAssociations::deleteByWcId($this->wcProduct->get_id());
        ProductAssociations::deleteByMoloniId($this->moloniVariant['productId']);

        ProductAssociations::add(
            $this->wcProduct->get_id(),
            $this->wcProduct->get_parent_id(),
            $this->moloniVariant['productId'],
            $this->moloniParentProduct['productId']
        );
    }

    //            Sets            //

    private function setVisibility()
    {
        $this->props['visible'] = Boolean::YES;
    }

    private function setName()
    {
        $this->props['name'] = $this->wcProduct->get_name();
    }

    private function setProductId()
    {
        $this->props['productId'] = $this->moloniVariant['productId'] ?? 0;
    }

    private function setStock()
    {
        $hasStock = $this->wcProduct->managing_stock();

        $this->props['hasStock'] = $hasStock;

        if ($hasStock) {
            $warehouseId = defined('MOLONI_STOCK_SYNC_WAREHOUSE') ? (int)MOLONI_STOCK_SYNC_WAREHOUSE : 1;

            if ($warehouseId === 1) {
                $results = Warehouses::queryWarehouses();

                /** fail safe */
                $warehouseId = (int)$results[0]['warehouseId'];

                foreach ($results as $result) {
                    if ((bool)$result['isDefault'] === true) {
                        $warehouseId = (int)$result['warehouseId'];

                        break;
                    }
                }
            }

            $this->props['warehouseId'] = $warehouseId;
            $this->props['warehouses'] = [[
                'warehouseId' => $warehouseId,
                'stock' => (float)$this->wcProduct->get_stock_quantity()
            ]];
        }
    }

    private function setPrice()
    {
        $this->props['price'] = (float)wc_get_price_excluding_tax($this->wcProduct);
    }

    private function setSummary()
    {
        $this->props['summary'] = $this->wcProduct->get_short_description() ?? '';
    }

    private function setNotes()
    {
        $this->props['notes'] = $this->wcProduct->get_description() ?? '';
    }

    private function setPropertyPairs()
    {
        $this->props['propertyPairs'] = $this->propertyPairs;
    }

    public function setMoloniParentProduct(array $moloniParentProduct)
    {
        $this->moloniParentProduct = $moloniParentProduct;
    }

    //            Gets            //

    public function getProps(): array
    {
        return $this->props;
    }

    public function getPropertyPairs(): ?array
    {
        return $this->propertyPairs;
    }

    public function getWcProduct(): ?WC_Product
    {
        return $this->wcProduct;
    }

    public function getMoloniVariant(): array
    {
        return $this->moloniVariant;
    }

    //            Auxiliary            //

    private function variantExists(): bool
    {
        return !empty($this->moloniVariant);
    }

    private function parentProductExists(): bool
    {
        return !empty($this->moloniParentProduct);
    }
}