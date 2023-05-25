<?php

namespace MoloniES\Hooks;

use MoloniES\Plugin;

class ProductSetStock
{
    public $parent;

    /**
     * Constructor
     *
     * @see https://stackoverflow.com/questions/39294861/which-hooks-are-triggered-when-woocommerce-product-stock-is-updated
     */
    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('woocommerce_product_set_stock', [$this, 'woocommerceProductSetStock']);
        add_action('woocommerce_variation_set_stock', [$this, 'woocommerceVariationSetStock']);
    }

    public function woocommerceProductSetStock()
    {

    }

    public function woocommerceVariationSetStock()
    {

    }
}