<?php

namespace MoloniES\Services\MoloniProduct\Page;

use MoloniES\Helpers\MoloniProduct;
use WC_Product;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\Domains;
use MoloniES\Exceptions\HelperException;
use MoloniES\Services\MoloniProduct\Helpers\Variants\ParseProductProperties;
use MoloniES\Services\WcProduct\Helpers\Variations\FindVariation;
use MoloniES\Tools\ProductAssociations;
use MoloniES\Traits\SettingsTrait;

class CheckProduct
{
    use SettingsTrait;

    private $product;
    private $warehouseId;
    private $company;

    private $rows = [];

    public function __construct(array $product, int $warehouseId, array $company)
    {
        $this->product = $product;
        $this->warehouseId = $warehouseId;
        $this->company = $company;
    }

    public function run()
    {
        if (empty($this->product['variants'])) {
            $this->checkNormalProduct($this->product);

            return;
        }

        $this->checkVariationsProduct($this->product);
    }

    //            Privates            //

    private function addRow(array $product)
    {
        $this->rows[] = [
            'tool_show_create_button' => false,
            'tool_show_update_stock_button' => false,
            'tool_alert_message' => [],
            'wc_product_id' => 0,
            'wc_product_parent_id' => 0,
            'wc_product_link' => '',
            'wc_product_object' => null,
            'moloni_product_id' => $product['productId'],
            'moloni_product_array' => $product,
            'moloni_product_link' => ''
        ];
    }

    //            Checks            //

    private function checkNormalProduct(array $mlProduct)
    {
        /** Add new table row */
        $this->addRow($mlProduct);

        /** Get current row */
        end($this->rows);
        $row = &$this->rows[key($this->rows)];

        $this->createMoloniLink($row);

        if (in_array(strtolower($mlProduct['reference']), ['taxa', 'fee', 'tarifa', 'envio', 'shipping', 'envío'])) {
            $row['tool_alert_message'][] = __('Product blocked', 'moloni_es');
            return;
        }

        $wcProduct = $this->fetchWcProduct($mlProduct);

        if (empty($wcProduct)) {
            $row['tool_show_create_button'] = true;
            $row['tool_alert_message'][] = __('Product not found in WooCommerce store', 'moloni_es');

            return;
        }

        $row['wc_product_id'] = $wcProduct->get_id();
        $row['wc_product_parent_id'] = $wcProduct->get_parent_id();
        $row['wc_product_object'] = $wcProduct;

        $this->createWcLink($row);

        if ($wcProduct->is_type('variable') && $wcProduct->has_child()) {
            $row['tool_alert_message'][] = __('Product types do not match', 'moloni_es');

            return;
        }

        if (!empty($mlProduct['hasStock']) !== $wcProduct->managing_stock()) {
            $row['tool_alert_message'][] = __('Different stock control status', 'moloni_es');

            return;
        }

        if (!empty($mlProduct['hasStock'])) {
            $wcStock = (int)$wcProduct->get_stock_quantity();
            $moloniStock = (int)MoloniProduct::parseMoloniStock($mlProduct, $this->warehouseId);

            if ($wcStock !== $moloniStock) {
                $row['tool_show_update_stock_button'] = true;

                $message = __('Stock does not match in WooCommerce and Moloni', 'moloni_es');
                $message .= " (Moloni: $moloniStock | WooCommerce: $wcStock)";

                $row['tool_alert_message'][] = $message;
            }
        }
    }

    private function checkVariationsProduct(array $mlProduct)
    {
        /** Add new table row */
        $this->addRow($mlProduct);

        /** Get current row */
        end($this->rows);
        $parentRow = &$this->rows[key($this->rows)];

        $this->createMoloniLink($parentRow);

        if (!$this->isSyncProductWithVariantsActive()) {
            $parentRow['tool_alert_message'][] = __('Synchronization of products with variants is disabled', 'moloni_es');

            return;
        }

        $wcProduct = $this->fetchWcProduct($mlProduct);

        if (empty($wcProduct)) {
            $parentRow['tool_show_create_button'] = true;
            $parentRow['tool_alert_message'][] = __('Product not found in WooCommerce store', 'moloni_es');

            return;
        }

        $parentRow['wc_product_id'] = $wcProduct->get_id();
        $parentRow['wc_product_parent_id'] = $wcProduct->get_parent_id();
        $parentRow['wc_product_object'] = $wcProduct;

        $this->createWcLink($parentRow);

        if (!$wcProduct->is_type('variable') || !$wcProduct->has_child()) {
            $parentRow['tool_alert_message'][] = __('Product types do not match', 'moloni_es');

            return;
        }

        try {
            $wcParentAttributes = (new ParseProductProperties($wcProduct))->handle();
        } catch (HelperException $e) {
            $parentRow['tool_alert_message'][] = __('Error parsing product properties', 'moloni_es');

            return;
        }

        foreach ($mlProduct['variants'] as $mlVariant) {
            /** Add new table row */
            $this->addRow($mlVariant);

            /** Get current row */
            end($this->rows);
            $childRow = &$this->rows[key($this->rows)];

            if ((int)$mlVariant['visible'] === Boolean::NO) {
                $childRow['tool_alert_message'][] = __('Variant is not visible', 'moloni_es');;

                continue;
            }

            $wcVariation = (new FindVariation($wcParentAttributes, $mlVariant))->run();

            if (empty($wcVariation)) {
                $childRow['tool_alert_message'][] = __('Variation not found in WooCommerce', 'moloni_es');

                continue;
            }

            $childRow['wc_product_id'] = $wcVariation->get_id();
            $childRow['wc_product_parent_id'] = $wcVariation->get_parent_id();
            $childRow['wc_product_object'] = $wcVariation;

            if (!empty($mlVariant['hasStock']) !== $wcVariation->managing_stock()) {
                $childRow['tool_alert_message'][] = __('Different stock control status', 'moloni_es');

                continue;
            }

            if (!empty($mlVariant['hasStock'])) {
                $wcStock = (int)$wcVariation->get_stock_quantity();
                $moloniStock = (int)MoloniProduct::parseMoloniStock($mlVariant, $this->warehouseId);

                if ($wcStock !== $moloniStock) {
                    $childRow['tool_show_update_stock_button'] = true;

                    $message = __('Stock does not match in WooCommerce and Moloni', 'moloni_es');
                    $message .= " (Moloni: $moloniStock | WooCommerce: $wcStock)";

                    $childRow['tool_alert_message'][] = $message;
                }
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
            include MOLONI_ES_TEMPLATE_DIR . 'Blocks/MoloniProduct/ProductRow.php';
        }

        return ob_get_clean() ?: '';
    }

    //            Auxiliary            //

    private function createMoloniLink(array &$row)
    {
        $row['moloni_product_link'] = Domains::AC;
        $row['moloni_product_link'] .= $this->company['slug'];
        $row['moloni_product_link'] .= '/productCategories/products/all/';
        $row['moloni_product_link'] .= $row['moloni_product_array']['productId'];
    }

    private function createWcLink(array &$row)
    {
        $wcProductId = $row['wc_product_id'];

        $row['wc_product_link'] = admin_url("post.php?post=$wcProductId&action=edit");
    }

    private function fetchWcProduct(array $product): ?WC_Product
    {
        /** Fetch by our associaitons table */

        $association = ProductAssociations::findByMoloniId($product['productId']);

        if (!empty($association)) {
            $wcProduct = wc_get_product($association['wc_product_id']);

            if (!empty($wcProduct)) {
                return $wcProduct;
            }

            ProductAssociations::deleteById($association['id']);
        }

        /** Fetch by reference */

        $wcProductId = wc_get_product_id_by_sku($product['reference']);

        if ($wcProductId > 0) {
            return wc_get_product($wcProductId);
        }

        return null;
    }
}
