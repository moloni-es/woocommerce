<?php

namespace MoloniES\Hooks;

use Exception;
use WC_Product;
use MoloniES\API\Products;
use MoloniES\Enums\Boolean;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Plugin;
use MoloniES\Start;
use MoloniES\Tools\ProductAssociations;

/**
 * Class OrderView
 * Add a Moloni Windows to when user is in the product view
 * @package Moloni\Hooks
 */
class ProductView
{
    /** @var Plugin  */
    public $parent;

    /** @var WC_Product */
    public $wcProduct;

    /** @var array */
    public $moloniProduct = [];

    private $allowedPostTypes = ["product"];

    /**
     * Contructor
     *
     * @param Plugin $parent
     */
    public function __construct(Plugin $parent)
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
                $this->wcProduct = wc_get_product(get_the_ID());

                if (!$this->wcProduct || $this->wcProduct->get_sku() === '') {
                    return null;
                }

                try {
                    $this->fetchMoloniProduct();

                    if (empty($this->moloniProduct)) {
                        echo __("Product not found in Moloni", 'moloni_es');
                        return null;
                    }

                    $this->showProductDetails();
                } catch (APIExeption $e) {
                    echo __("Error getting product", 'moloni_es');
                    return null;
                }
            } else {
                echo __("Moloni login invalid", 'moloni_es');
            }
        } catch (Exception $exception) {}
    }

    private function showProductDetails()
    {
        ?>
        <div>
            <p>
                <b><?= __("Reference: ", 'moloni_es') ?></b> <?= $this->moloniProduct['reference'] ?><br>
                <b><?= __("Price: ", 'moloni_es') ?></b> <?= $this->moloniProduct['price'] ?>€<br>

                <?php if ((int)$this->moloniProduct['hasStock'] === Boolean::YES) : ?>
                    <b><?= __("Stock: ", 'moloni_es') ?></b> <?= $this->moloniProduct['stock'] ?>
                <?php endif; ?>

                <?php

                echo "<pre style='display: none'>";
                print_r($this->wcProduct->get_meta_data());
                print_r($this->wcProduct->get_default_attributes());
                print_r($this->wcProduct->get_attributes());
                print_r($this->wcProduct->get_data());
                echo "</pre>";

                ?>
            </p>
            <?php if (defined("COMPANY_SLUG")) : ?>
                <a type="button"
                   class="button button-primary"
                   target="_BLANK"
                   href="<?= esc_url('https://ac.moloni.es/' . COMPANY_SLUG . '/productCategories/products/' . $this->moloniProduct['productId']) ?>"
                > <?= __("See product", 'moloni_es') ?> </a>
            <?php endif; ?>
        </div>
        <?php
    }

    //          REQUESTS          //

    /**
     * Fetch Moloni Product
     *
     * @throws APIExeption
     */
    private function fetchMoloniProduct()
    {
        /** Fetch by our associations table */

        $association = ProductAssociations::findByWcId($this->wcProduct->get_id());

        if (!empty($association)) {
            $byId = Products::queryProduct(['productId' => (int)$association['ml_product_id']]);
            $byId = $byId['data']['product']['data'] ?? [];

            if (!empty($byId)) {
                $this->moloniProduct = $byId;

                return;
            }

            ProductAssociations::deleteById($association['id']);
        }

        $variables = [
            'options' => [
                'filter' => [
                    [
                        'field' => 'reference',
                        'comparison' => 'eq',
                        'value' => $this->wcProduct->get_sku(),
                    ],
                    [
                        'field' => 'visible',
                        'comparison' => 'gte',
                        'value' => '0',
                    ]
                ],
                "includeVariants" => true
            ]
        ];

        $byReference = Products::queryProducts($variables);

        if (!empty($byReference) && isset($byReference[0]['productId'])) {
            $this->moloniProduct = $byReference[0];
        }
    }
}
