<?php

namespace MoloniES\Services\MoloniProduct\Variant;

use MoloniES\Exceptions\HelperException;
use MoloniES\Exceptions\ServiceException;
use MoloniES\Helpers\MoloniWarehouse;
use WC_Product;
use WC_Product_Variation;
use MoloniES\Enums\Boolean;
use MoloniES\Services\MoloniProduct\Helpers\Variants\FindVariant;
use MoloniES\Tools\ProductAssociations;
use MoloniES\Traits\SyncFieldsSettingsTrait;

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

    /**
     * WooCommerce image URL
     *
     * @var string
     */
    private $image = '';

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

        if ($this->productShouldSyncImage()) {
            $this->setImage();
        }
    }

    //            Privates            //

    public function createAssociation()
    {
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

    /**
     * Set stock
     *
     * @throws ServiceException
     */
    private function setStock()
    {
        $hasStock = $this->wcProduct->managing_stock();

        if ($hasStock) {
            $warehouseId = defined('MOLONI_STOCK_SYNC_WAREHOUSE') ? (int)MOLONI_STOCK_SYNC_WAREHOUSE : 1;

            if ($warehouseId === 1) {
                try {
                    $warehouseId = MoloniWarehouse::getDefaultWarehouse();
                } catch (HelperException $e) {
                    throw new ServiceException($e->getMessage(), $e->getData());
                }
            }

            $this->props['warehouseId'] = $warehouseId;
            $warehouses = [
                'warehouseId' => $warehouseId,
            ];

            /** New variants cant have stock if parent product already exists */
            if (!$this->parentProductExists()) {
                $warehouses['stock'] = (float)$this->wcProduct->get_stock_quantity();
            }

            $this->props['warehouses'] = [$warehouses];
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

    private function setImage()
    {
        $wcImageId = $this->wcProduct->get_image_id();

        $url = wp_get_attachment_url($wcImageId);

        if ($url) {
            $uploads = wp_upload_dir();

            $image = str_replace($uploads['baseurl'], $uploads['basedir'], $url);
        }

        $this->image = $image ?? '';
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

    public function getImage(): string
    {
        return $this->image ?? '';
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

    public function getMoloniVariantProductId(): int
    {
        if (empty($this->moloniVariant)) {
            return 0;
        }

        return (int)($this->moloniVariant['productId'] ?? 0);
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
