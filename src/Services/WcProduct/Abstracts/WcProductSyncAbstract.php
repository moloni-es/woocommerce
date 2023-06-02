<?php

namespace MoloniES\Services\WcProduct\Abstracts;

use WP_Term;
use stdClass;
use WC_Product;
use WC_Product_Attribute;
use MoloniES\Enums\Boolean;
use MoloniES\Helpers\MoloniProduct;
use MoloniES\Traits\SyncFieldsSettingsTrait;
use MoloniES\Services\WcProduct\Interfaces\WcSyncInterface;
use MoloniES\Services\WcProduct\Helpers\FetchImageFromMoloni;
use MoloniES\Services\WcProduct\Helpers\FetchWcCategoriesFromMoloniCategoryId;

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

        $categoryIds = (new FetchWcCategoriesFromMoloniCategoryId($moloniCategoryId))->get();

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
        $position = 0;

        foreach ($attributes as $name => $options) {
            $attrId = wc_attribute_taxonomy_id_by_name($name);

            if (empty($attrId)) {
                $attrId = wc_create_attribute([
                    'name' => $name
                ]);

                $taxonomy = wc_get_attribute($attrId)->slug;

                register_taxonomy($taxonomy, ['product']);
            } else {
                $taxonomy = wc_get_attribute($attrId)->slug;
            }

            $attributeObj = new WC_Product_Attribute();

            $termsIds = [];

            foreach ($options as $option) {
                term_exists($option, $taxonomy, $attrId);

                if (empty($termId)) {
                    wp_create_term($option, $taxonomy);
                }

                $termsIds[] = $option;
            }

            $attributeObj->set_id($attrId);
            $attributeObj->set_name($taxonomy);
            $attributeObj->set_options($termsIds);
            $attributeObj->set_position($position);
            $attributeObj->set_visible(true);
            $attributeObj->set_variation(true);

            $productAttributes[] = $attributeObj;

            $position++;
        }

        $this->wcProduct->set_attributes($productAttributes);
    }

    protected function setVariationOptions()
    {
        $attributes = [];

        foreach ($this->moloniProduct["propertyPairs"] as $value) {
            $propertyName = trim($value['property']["name"]);
            $propertyValue = trim($value['propertyValue']["value"]);

            $attrId = wc_attribute_taxonomy_id_by_name($propertyName);
            /** @var stdClass|null $attribute */
            $attribute = wc_get_attribute($attrId);

            if (empty($attribute)) {
                continue;
            }

            $taxonomy = $attribute->slug;

            /** @var WP_Term|null $term */
            $term = get_term_by('name', $propertyValue, $taxonomy);

            if (empty($term)) {
                continue;
            }

            $attributes[$taxonomy] = $term->slug;
        }

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
