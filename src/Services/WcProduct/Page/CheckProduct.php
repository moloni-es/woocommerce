<?php

namespace MoloniES\Services\WcProduct\Page;

use WC_Product;
use MoloniES\Enums\Domains;
use MoloniES\Helpers\MoloniProduct;

class CheckProduct
{
    private $product;
    private $warehouseId;
    private $company;

    private $rows = [];

    public function __construct(WC_Product $product, int $warehouseId, array $company)
    {
        $this->product = $product;
        $this->warehouseId = $warehouseId;
        $this->company = $company;
    }

    public function run()
    {
        $this->checkProduct($this->product);
    }

    //            Privates            //

    private function checkProduct($product)
    {
        $this->rows[] = [
            'tool_show_create_button' => false,
            'tool_show_update_stock_button' => false,
            'tool_alert_message' => '',
            'wc_product_id' => $product->get_id(),
            'wc_product_parent_id' => $product->get_parent_id(),
            'wc_product_link' => '',
            'wc_product_object' => $product,
            'moloni_product_id' => 0,
            'moloni_product_array' => [],
            'moloni_product_link' => ''
        ];

        end($this->rows);
        $row = &$this->rows[key($this->rows)];

        if ($product->is_type('variable') && $product->has_child()) {
            $this->checkParentProduct($row, $product);

            $children = $product->get_children();

            foreach ($children as $child) {
                $childObject = wc_get_product($child);

                $this->checkProduct($childObject);
            }
        } else {
            $this->checkNormalProduct($row, $product);
        }
    }

    private function checkParentProduct(array &$row, WC_Product $product)
    {
        $this->createWcLink($row);

        if ($product->managing_stock()) {
            $row['tool_alert_message'] = __('Gestão de stock deve ser efetuada ao nível das variações', 'moloni_es');

            return;
        }
    }

    private function checkNormalProduct(array &$row, WC_Product $product)
    {
        /** Child products do not have their own page */
        if (empty($product->get_parent_id())) {
            $this->createWcLink($row);
        }

        if (empty($product->get_sku())) {
            $row['tool_alert_message'] = __('Produto WooCommerce sem referência', 'moloni_es');

            return;
        }

        $mlProduct = [];

        if (empty($mlProduct)) {
            $row['tool_show_create_button'] = true;
            $row['tool_alert_message'] = __('Produto não encontrado na conta Moloni', 'moloni_es');

            return;
        }

        $mlProduct = $mlProduct[0];

        $row['moloni_product_id'] = $mlProduct['productId'];
        $row['moloni_product_array'] = $mlProduct;

        $this->createMoloniLink($row);

        if (!empty($mlProduct['has_stock']) !== $product->managing_stock()) {
            $row['tool_alert_message'] = __('Estado do controlo de stock diferente', 'moloni_es');

            return;
        }

        if (!empty($mlProduct['has_stock'])) {
            $wcStock = (int)$product->get_stock_quantity();
            $moloniStock = (int)MoloniProduct::parseMoloniStock($mlProduct, $this->warehouseId);

            if ($wcStock !== $moloniStock) {
                $row['tool_show_update_stock_button'] = true;
                $row['tool_alert_message'] = __('Stock não coincide no WooCommerce e Moloni', 'moloni_es');
                $row['tool_alert_message'] .= " (Moloni: $moloniStock | WooCommerce: $wcStock)";

                return;
            }
        }
    }

    //            Gets            //

    public function getRows(): array
    {
        return $this->rows;
    }

    public function getRowsHtml(): string
    {
        ob_start();

        foreach ($this->rows as $row) {
            include MOLONI_ES_TEMPLATE_DIR . 'Blocks/WcProducts/ProductRow.php';
        }

        return ob_get_clean() ?: '';
    }

    //            Auxiliary            //

    private function createMoloniLink(array &$row)
    {
        $row['moloni_product_link'] = Domains::AC . '/';
        $row['moloni_product_link'] .= $this->company['slug'];
        $row['moloni_product_link'] .= '/productCategories/products/all/';
        $row['moloni_product_link'] .= $row['moloni_product_array']['productId'];
    }

    private function createWcLink(array &$row)
    {
        $wcProductId = $row['wc_product_object']->get_id();

        $row['wc_product_link'] = admin_url("post.php?post=$wcProductId&action=edit");
    }
}
