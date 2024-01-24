<?php

namespace MoloniES\Services\MoloniProduct\Page;

use MoloniES\Enums\Domains;
use MoloniES\Helpers\MoloniProduct;
use MoloniES\Tools\ProductAssociations;
use MoloniES\Traits\SettingsTrait;
use WC_Product;

class CheckProduct
{
    use SettingsTrait;

    private $product;
    private $warehouseId;
    private $company;

    private $row;

    public function __construct(array $product, int $warehouseId, array $company)
    {
        $this->product = $product;
        $this->warehouseId = $warehouseId;
        $this->company = $company;
    }

    public function run()
    {
        $this->row = [
            'tool_show_create_button' => false,
            'tool_show_update_stock_button' => false,
            'tool_alert_message' => [],
            'wc_product_id' => 0,
            'wc_product_parent_id' => 0,
            'wc_product_link' => '',
            'wc_product_object' => null,
            'moloni_product_id' => $this->product['product_id'],
            'moloni_product_array' => $this->product,
            'moloni_product_link' => ''
        ];

        $this->createMoloniLink();

        if (in_array(strtolower($this->product['reference']), ['taxa', 'fee', 'tarifa', 'envio', 'shipping', 'envío'])) {
            $this->row['tool_alert_message'][] = __('Product blocked', 'moloni_es');
            return;
        }

        $wcProductId = $this->fetchWcProduct();

        if (empty($wcProductId)) {
            $this->row['tool_show_create_button'] = true;
            $this->row['tool_alert_message'][] = __('Product not found in WooCommerce store', 'moloni_es');

            return;
        }

        $wcProduct = wc_get_product($wcProductId);

        $this->row['wc_product_id'] = $wcProduct->get_id();
        $this->row['wc_product_parent_id'] = $wcProduct->get_parent_id();
        $this->row['wc_product_object'] = $wcProduct;

        $this->createWcLink();

        if ($wcProduct->is_type('variable')) {
            $this->row['tool_alert_message'][] = __('Produto WooCommerce tem variantes', 'moloni_es');

            return;
        }
    }

    //            Gets            //

    public function getRow(): array
    {
        return $this->row;
    }

    public function getRowsHtml(): string
    {
        $row = $this->row;

        ob_start();

        include MOLONI_ES_TEMPLATE_DIR . 'Blocks/MoloniProduct/ProductRow.php';

        return ob_get_clean() ?: '';
    }
    //            Auxiliary            //

    private function createMoloniLink()
    {
        $row['moloni_product_link'] = Domains::AC;
        $row['moloni_product_link'] .= $this->company['slug'];
        $row['moloni_product_link'] .= '/productCategories/products/all/';
        $row['moloni_product_link'] .= $row['moloni_product_array']['productId'];
    }

    private function createWcLink()
    {
        $wcProductId = $this->row['wc_product_id'];

        $this->row['wc_product_link'] = admin_url("post.php?post=$wcProductId&action=edit");
    }

    private function fetchWcProduct(): ?WC_Product
    {
        /** Fetch by our associaitons table */

        $association = ProductAssociations::findByMoloniId($this->product['productId']);

        if (!empty($association)) {
            $wcProduct = wc_get_product($association['wc_product_id']);

            if (!empty($wcProduct)) {
                return $wcProduct;
            }

            ProductAssociations::deleteById($association['id']);
        }

        /** Fetch by reference */

        $wcProductId = wc_get_product_id_by_sku($this->product['reference']);

        if ($wcProductId > 0) {
            return wc_get_product($wcProductId);
        }

        return null;
    }
}
