<?php

namespace MoloniES\Services\WcProduct\Create;

use MoloniES\Enums\Boolean;
use MoloniES\Helpers\MoloniProduct;
use MoloniES\Helpers\ProductAssociations;
use MoloniES\Services\WcProduct\Abstracts\WcProductSyncAbstract;
use MoloniES\Services\WcProduct\Helpers\FetchImageFromMoloni;
use MoloniES\Services\WcProduct\Helpers\FetchWcCategoriesFromMoloniCategoryId;
use MoloniES\Storage;
use WC_Data_Exception;
use WC_Product;
use WC_Product_Attribute;

class CreateParentProduct extends WcProductSyncAbstract
{
    private $moloniProduct;
    private $wcProduct;

    public function __construct(array $moloniProduct)
    {
        $this->moloniProduct = $moloniProduct;
        $this->wcProduct = new WC_Product();
    }

    /**
     * Runner
     *
     * @throws WC_Data_Exception
     */
    public function run()
    {
        $this->wcProduct->set_name($this->moloniProduct['name'] ?? '');
        $this->wcProduct->set_sku($this->moloniProduct['reference'] ?? '');

        if (empty($this->moloniProduct['taxes'])) {
            $this->wcProduct->set_tax_status('none');
        } else {
            $this->wcProduct->set_tax_status('taxable');
        }

        /** Stock is managed in variations level */
        $this->wcProduct->set_manage_stock(false);

        $categoryIds = (new FetchWcCategoriesFromMoloniCategoryId($this->moloniProduct['productCategory']['productCategoryId'] ?? 0))->get();

        if (!empty($categoryIds)) {
            $this->wcProduct->set_category_ids($categoryIds);
        }

        if ($this->productShouldSyncDescription()) {
            $this->wcProduct->set_short_description($this->moloniProduct['summary'] ?? '');
            $this->wcProduct->set_description($this->moloniProduct['notes'] ?? '');
        }

        if ($this->productShouldSyncVisibility()) {
            $this->wcProduct->set_catalog_visibility((int)$this->moloniProduct['visible'] === Boolean::YES ? 'visible' : 'hidden');
        }

        if ($this->productShouldSyncEAN()) {
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

        if ($this->productShouldSyncImage()) {
            if (empty($this->moloniProduct['img'])) {
                $this->wcProduct->set_image_id('');
            } else {
                $imageId = (new FetchImageFromMoloni($this->moloniProduct['img']))->get();

                if ($imageId > 0) {
                    $this->wcProduct->set_image_id($imageId);
                }
            }
        }

        $this->setAttributes();

        $this->wcProduct->save();

        $this->createAssociation();
    }

    public function saveLog()
    {
        $message = sprintf(__('Parent product created in WooCommerce ({0})', 'moloni_es'), $this->wcProduct->get_sku());

        Storage::$LOGGER->info($message, [
            'moloniId' => $this->moloniProduct['productId'],
            'moloniParentId' => 0,
            'wcId' => $this->wcProduct->get_id(),
            'wcParentId' => 0
        ]);
    }

    //            Gets            //

    public function getWcProduct(): WC_Product
    {
        return $this->wcProduct;
    }

    public function getMoloniProduct(): array
    {
        return $this->moloniProduct;
    }

    //            Privates            //

    private function setAttributes()
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

    //            Auxliary            //

    protected function createAssociation()
    {
        ProductAssociations::add(
            $this->wcProduct->get_id(),
            0,
            $this->moloniProduct['productId'],
            0
        );
    }
}