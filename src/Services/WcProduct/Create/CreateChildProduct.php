<?php

namespace MoloniES\Services\WcProduct\Create;

use MoloniES\Services\WcProduct\Abstracts\WcProductSyncAbstract;
use MoloniES\Storage;
use MoloniES\Tools\ProductAssociations;
use WC_Product;
use WC_Product_Variation;

/**
 * God's gifts
 *
 * @see https://stackoverflow.com/questions/47518280/create-programmatically-a-woocommerce-product-variation-with-new-attribute-value
 * @see https://stackoverflow.com/questions/52937409/create-programmatically-a-product-using-crud-methods-in-woocommerce-3/52941994#52941994
 * @see https://stackoverflow.com/questions/53944532/auto-set-specific-attribute-term-value-to-purchased-products-on-woocommerce
 * @see https://stackoverflow.com/questions/47518333/create-programmatically-a-variable-product-and-two-new-attributes-in-woocommerce
 */
class CreateChildProduct extends WcProductSyncAbstract
{
    public function __construct(array $moloniProduct, WC_Product $wcParentProduct)
    {
        $this->moloniProduct = $moloniProduct;

        $this->wcProduct = new WC_Product_Variation();
        $this->wcProductParent = $wcParentProduct;
    }

    public function run()
    {
        $this->setParent();
        $this->setName();
        $this->setReference();

        if ($this->productShouldSyncDescription()) {
            $this->setDescripton();
        }

        if ($this->productShouldSyncPrice()) {
            $this->setPrice();
        }

        if ($this->productShouldSyncStock()) {
            $this->setStock();
        }

        if ($this->productShouldSyncImage()) {
            $this->setImage();
        }

        $this->setVariationOptions();

        $this->wcProduct->save();

        $this->createAssociation();
    }

    public function saveLog()
    {
        $message = sprintf(__('Variation product created in WooCommerce (%s)', 'moloni_es'), $this->wcProduct->get_sku());

        Storage::$LOGGER->info($message, [
            'tag' => 'service:wcproduct:child:create',
            'moloniId' => $this->moloniProduct['productId'],
            'moloniParentId' => $this->moloniProduct['parent']['productId'],
            'wcId' => $this->wcProduct->get_id(),
            'wcParentId' => $this->wcProduct->get_parent_id(),
        ]);
    }

    //            Auxliary            //

    protected function createAssociation()
    {
        ProductAssociations::add(
            $this->wcProduct->get_id(),
            $this->wcProduct->get_parent_id(),
            $this->moloniProduct['productId'],
            $this->moloniProduct['parent']['productId']
        );
    }
}
