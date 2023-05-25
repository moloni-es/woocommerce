<?php

namespace MoloniES\Services\WcProduct\Abstracts;

use WC_Product;
use MoloniES\Enums\Boolean;
use MoloniES\Helpers\MoloniProduct;
use MoloniES\Traits\SyncFieldsSettingsTrait;
use MoloniES\Services\WcProduct\Interfaces\WcSyncInterface;
use MoloniES\Services\WcProduct\Helpers\FetchImageFromMoloni;
use MoloniES\Services\WcProduct\Helpers\FetchWcCategoriesFromMoloniCategoryId;
use WC_Product_Attribute;

abstract class WcProductSyncAbstract implements WcSyncInterface
{
    use SyncFieldsSettingsTrait;

    /**
     * WooCommerce product
     *
     * @var WC_Product|null
     */
    protected $wcProduct;

    /**
     * WooCommerce parent product
     *
     * @var WC_Product|null
     */
    protected $wcProductParent;

    /**
     * Moloni Product
     *
     * @var array|null
     */
    protected $moloniProduct;

    /**
     * Moloni parent product
     *
     * @var array|null
     */
    protected $moloniProductParent;

    //            Sets            //

    protected function setName()
    {
        $this->wcProduct->set_name($this->moloniProduct['name'] ?? '');
    }

    protected function setReference()
    {
        $this->wcProduct->set_sku($this->moloniProduct['reference'] ?? '');
    }

    protected function setPrice()
    {
        if (wc_prices_include_tax()) {
            $this->wcProduct->set_regular_price($this->moloniProduct['priceWithTaxes']);
        } else {
            $this->wcProduct->set_regular_price($this->moloniProduct['price']);
        }
    }

    protected function setTaxes()
    {
        if (empty($this->moloniProduct['taxes'])) {
            $this->wcProduct->set_tax_status('none');
        } else {
            $this->wcProduct->set_tax_status('taxable');
        }
    }

    protected function setCategories()
    {
        $moloniCategoryId = $this->moloniProduct['productCategory']['productCategoryId'] ?? 0;

        $categoryIds = (new FetchWcCategoriesFromMoloniCategoryId())->get($moloniCategoryId);

        if (!empty($categoryIds)) {
            $this->wcProduct->set_category_ids($categoryIds);
        }
    }

    protected function setDescripton()
    {
        $this->wcProduct->set_short_description($this->moloniProduct['summary'] ?? '');
        $this->wcProduct->set_description($this->moloniProduct['notes'] ?? '');
    }

    protected function setVisibility()
    {
        $this->wcProduct->set_catalog_visibility((int)$this->moloniProduct['visible'] === Boolean::YES ? 'visible' : 'hidden');
    }

    protected function setStock()
    {
        $hasStock = (bool)$this->moloniProduct['hasStock'];

        $this->wcProduct->set_manage_stock($hasStock);

        if ($hasStock) {
            $stock = MoloniProduct::parseMoloniStock(
                $this->moloniProduct,
                defined('HOOK_STOCK_SYNC_WAREHOUSE') ? (int)HOOK_STOCK_SYNC_WAREHOUSE : 1
            );

            $this->wcProduct->set_stock_quantity($stock);
            $this->wcProduct->set_low_stock_amount($this->moloniProduct['minStock']);
        }
    }

    protected function setEan()
    {
        foreach ($this->moloniProduct['identifications'] as $identification) {
            if ($identification['type'] === 'EAN13') {
                if (!$this->wcProduct->get_meta('_barcode')) {
                    $this->wcProduct->add_meta_data('_barcode', $identification['text']);
                } else {
                    $this->wcProduct->update_meta_data('_barcode', $identification['text']);
                }

                break;
            }
        }
    }

    protected function setImage()
    {
        $moloniImage = $this->moloniProduct['img'] ?? '';

        if (empty($moloniImage)) {
            $this->wcProduct->set_image_id('');
        } else {
            $imageId = $this->wcProduct->get_image_id();

            if ($imageId > 0) {
                $currentImageTitle = get_the_title($imageId);

                if (str_contains($moloniImage, $currentImageTitle)) {
                    return;
                }
            }

            $imageId = (new FetchImageFromMoloni($this->moloniProduct['img']))->get();

            if ($imageId > 0) {
                $this->wcProduct->set_image_id($imageId);
            }
        }
    }

    protected function setAttributes()
    {
        $attributes = MoloniProduct::parseParentVariantsAttributes($this->moloniProduct);
        $productAttributes = [];

        foreach ($attributes as $name => $options) {
            $attrId = wc_attribute_taxonomy_id_by_name($name);

            if (empty($attrId)) {
                $attrId = wc_create_attribute([
                    'name' => $name,
                ]);
            }

            $slug = wc_get_attribute($attrId)->slug;

            foreach ($options as $option) {
                if (!term_exists($option, '', $attrId)) {
                    wp_create_term($option, $slug);
                }
            }

            $attributeObj = new WC_Product_Attribute();
            $attributeObj->set_id($attrId);
            $attributeObj->set_name($name);
            $attributeObj->set_options($options);
            $attributeObj->set_visible(true);
            $attributeObj->set_variation(true);

            $productAttributes = $attributeObj;
        }

        $this->wcProduct->set_attributes($productAttributes);
    }

    protected function setAttributesOptions()
    {
        $attributes = MoloniProduct::parseVariantAttributes($this->moloniProduct);

        $this->wcProduct->set_attributes($attributes);
    }

    protected function setParent()
    {
        $this->wcProduct->set_parent_id($this->wcProductParent->get_id());
    }

    //            Gets            //

    public function getWcProduct(): ?WC_Product
    {
        return $this->wcProduct;
    }

    public function getWcProductParent(): ?WC_Product
    {
        return $this->wcProductParent;
    }

    public function getMoloniProduct(): array
    {
        return $this->moloniProduct;
    }

    public function getMoloniProductParent(): ?array
    {
        return $this->moloniProductParent;
    }

    //            Abstracts            //

    protected abstract function createAssociation();
}