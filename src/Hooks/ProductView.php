<?php

namespace MoloniES\Hooks;

use Exception;
use MoloniES\Controllers\Product;
use MoloniES\Error;
use MoloniES\Plugin;
use MoloniES\Start;
use WC_Product;

/**
 * Class OrderView
 * Add a Moloni Windows to when user is in the product view
 * @package Moloni\Hooks
 */
class ProductView
{

    public $parent;

    /** @var WC_Product */
    public $product;

    /** @var Product */
    public $moloniProduct;


    private $allowedPostTypes = ["product"];

    /**
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('add_meta_boxes', [$this, 'moloni_add_meta_box']);
    }

    public function moloni_add_meta_box($post_type)
    {
        if (in_array($post_type, $this->allowedPostTypes)) {
            add_meta_box('woocommerce_product_options_general_product_data', 'Moloni', [$this, 'showMoloniView'], null, 'side');
        }
    }

    /**
     * @return null|void
     */
    public function showMoloniView()
    {
        try {
            if (Start::login(true)) {
                $this->product = wc_get_product(get_the_ID());

                if (!$this->product || $this->product->get_sku() === '') {
                    return null;
                }

                $this->moloniProduct = new Product($this->product);

                try {
                    if (!$this->moloniProduct->loadByReference()) {
                        echo sprintf(__("Product with reference %s not found", 'moloni_es'), $this->moloniProduct->reference);
                        return null;
                    }

                    $this->showProductDetails();
                } catch (Error $e) {
                    echo __("Error getting product", 'moloni_es');
                    return null;
                }
            } else {
                echo __("Moloni login invalid", 'moloni_es');
            }
        } catch (Exception $exception) {

        }
    }

    private function showProductDetails()
    {
        ?>
        <div>
            <p>
                <b><?= __("Reference: ", 'moloni_es') ?></b> <?= $this->moloniProduct->reference ?><br>
                <b><?= __("Price: ", 'moloni_es') ?></b> <?= $this->moloniProduct->price ?>€<br>
                <?php if ($this->moloniProduct->has_stock == 1) : ?>
                    <b><?= __("Stock: ", 'moloni_es') ?></b> <?= $this->moloniProduct->stock ?>
                <?php endif; ?>

                <?php if (defined("COMPANY_SLUG")) : ?>
                    <a type="button"
                       class="button button-primary"
                       target="_BLANK"
                       href="<?= esc_url('https://ac.moloni.es/' . COMPANY_SLUG . '/productCategories/products/' . $this->moloniProduct->product_id) ?>"
                       style="margin-top: 10px; float:right;"
                    > <?= __("See product", 'moloni_es') ?> </a>
                <?php endif; ?>

                <?php

                echo "<pre style='display: none'>";
                print_r($this->product->get_meta_data());
                print_r($this->product->get_default_attributes());
                print_r($this->product->get_attributes());
                print_r($this->product->get_data());
                echo "</pre>";

                ?>
            </p>
        </div>
        <?php
    }

}
