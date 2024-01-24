<?php

namespace MoloniES\Services\WcProduct\Page;

use WC_Product;
use MoloniES\API\Products;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\HelperException;
use MoloniES\Services\MoloniProduct\Helpers\Variants\FindVariant;
use MoloniES\Services\MoloniProduct\Helpers\Variants\GetOrUpdatePropertyGroup;
use MoloniES\Tools\ProductAssociations;
use MoloniES\Traits\SettingsTrait;
use MoloniES\Enums\Domains;
use MoloniES\Helpers\MoloniProduct;

class CheckProduct
{
    use SettingsTrait;

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
        if (!$this->product->is_type('variable') || !$this->product->has_child()) {
            $this->checkNormalProduct($this->product);
            return;
        }

        if ($this->isSyncProductWithVariantsActive()) {
            $this->checkVariableProduct($this->product);

            return;
        }

        $this->checkVariableProductAsSimple($this->product);
    }

    //            Privates            //

    private function addRow($product)
    {
        $this->rows[] = [
            'tool_show_create_button' => false,
            'tool_show_update_stock_button' => false,
            'tool_alert_message' => [],
            'wc_product_id' => $product->get_id(),
            'wc_product_parent_id' => $product->get_parent_id(),
            'wc_product_link' => '',
            'wc_product_object' => $product,
            'moloni_product_id' => 0,
            'moloni_product_array' => [],
            'moloni_product_link' => ''
        ];
    }

    //            Checks            //

    private function checkVariableProductAsSimple(WC_Product $product)
    {
        /** Add new table row */
        $this->addRow($product);

        /** Get current row */
        end($this->rows);
        $parentRow = &$this->rows[key($this->rows)];

        $this->createWcLink($parentRow);

        if ($product->managing_stock()) {
            $parentRow['tool_alert_message'][] = __('Inventory must be managed at the variations level', 'moloni_es');
        }

        foreach ($product->get_children() as $child) {
            $childObject = wc_get_product($child);

            $this->checkNormalProduct($childObject);
        }
    }

    private function checkVariableProduct(WC_Product $product)
    {
        /** Add new table row */
        $this->addRow($product);

        /** Get current row */
        end($this->rows);
        $parentRow = &$this->rows[key($this->rows)];

        $this->createWcLink($parentRow);

        if (empty($product->get_sku())) {
            $parentRow['tool_alert_message'][] = __('WooCommerce product without reference', 'moloni_es');

            return;
        }

        try {
            $mlProduct = $this->findMoloniProduct($product);
        } catch (APIExeption $e) {
            $parentRow['tool_alert_message'][] = __('Error fetching Moloni product', 'moloni_es');

            return;
        }

        if (empty($mlProduct)) {
            $parentRow['tool_show_create_button'] = true;
            $parentRow['tool_alert_message'][] = __('Product not found in Moloni account', 'moloni_es');

            return;
        }

        $parentRow['moloni_product_id'] = $mlProduct['productId'];
        $parentRow['moloni_product_array'] = $mlProduct;

        $this->createMoloniLink($parentRow);

        if (empty($mlProduct['variants'])) {
            $parentRow['tool_alert_message'][] = __('Product types do not match', 'moloni_es');

            return;
        }

        $targetId = $mlProduct['propertyGroup']['propertyGroupId'] ?? '';

        try {
            $propertyGroup = (new GetOrUpdatePropertyGroup($product, $targetId))->handle();
        } catch (HelperException $e) {
            $parentRow['tool_alert_message'][] = __('Error getting or updating property group', 'moloni_es');

            return;
        }

        $childIds = $product->get_children();

        foreach ($childIds as $childId) {
            $wcVariation = wc_get_product($childId);

            /** Add new table row */
            $this->addRow($wcVariation);

            /** Get current row */
            end($this->rows);
            $childRow = &$this->rows[key($this->rows)];

            if (empty($product->get_sku())) {
                $childRow['tool_alert_message'][] = __('WooCommerce variation without reference', 'moloni_es');

                continue;
            }

            /** Fetch matching Moloni variant */
            $moloniVariant = (new FindVariant(
                $wcVariation->get_id(),
                $wcVariation->get_sku(),
                $mlProduct['variants'],
                $propertyGroup['variations'][$childId] ?? []
            ))->run();

            /** Moloni variant not found */
            if (empty($moloniVariant)) {
                $message = __('Variation not found', 'moloni_es');
                $message .= ' ';
                $message .= '(' . $wcVariation->get_sku() . ')';

                $childRow['tool_alert_message'][] = $message;

                continue;
            }

            $childRow['moloni_product_id'] = $moloniVariant['productId'];
            $childRow['moloni_product_array'] = $moloniVariant;

            if (!empty($moloniVariant['hasStock']) !== $wcVariation->managing_stock()) {
                $childRow['tool_alert_message'][] = __('Different stock control status', 'moloni_es');

                continue;
            }

            if ($wcVariation->managing_stock()) {
                $wcStock = (int)$wcVariation->get_stock_quantity();
                $moloniStock = (int)MoloniProduct::parseMoloniStock($moloniVariant, $this->warehouseId);

                if ($wcStock !== $moloniStock) {
                    $childRow['tool_show_update_stock_button'] = true;

                    $message = __('Stock does not match in WooCommerce and Moloni', 'moloni_es');
                    $message .= " (Moloni: $moloniStock | WooCommerce: $wcStock)";

                    $childRow['tool_alert_message'][] = $message;
                }
            }
        }
    }

    private function checkNormalProduct(WC_Product $product)
    {
        /** Add new table row */
        $this->addRow($product);

        /** Get current row */
        end($this->rows);
        $row = &$this->rows[key($this->rows)];

        /** Child products do not have their own page */
        if (empty($product->get_parent_id())) {
            $this->createWcLink($row);
        }

        if (empty($product->get_sku())) {
            $row['tool_alert_message'][] = __('WooCommerce product without reference', 'moloni_es');

            return;
        }

        try {
            $mlProduct = $this->findMoloniProduct($product);
        } catch (APIExeption $e) {
            $row['tool_alert_message'][] = __('Error fetching Moloni product', 'moloni_es');

            return;
        }

        if (empty($mlProduct)) {
            $row['tool_show_create_button'] = true;
            $row['tool_alert_message'][] = __('Product not found in Moloni account', 'moloni_es');

            return;
        }

        $row['moloni_product_id'] = $mlProduct['productId'];
        $row['moloni_product_array'] = $mlProduct;

        $this->createMoloniLink($row);

        if (!empty($mlProduct['hasStock']) !== $product->managing_stock()) {
            $row['tool_alert_message'][] = __('Different stock control status', 'moloni_es');

            return;
        }

        if ($product->managing_stock()) {
            $wcStock = (int)$product->get_stock_quantity();
            $moloniStock = (int)MoloniProduct::parseMoloniStock($mlProduct, $this->warehouseId);

            if ($wcStock !== $moloniStock) {
                $row['tool_show_update_stock_button'] = true;

                $message = __('Stock does not match in WooCommerce and Moloni', 'moloni_es');
                $message .= " (Moloni: $moloniStock | WooCommerce: $wcStock)";

                $row['tool_alert_message'][] = $message;
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
        $row['moloni_product_link'] = Domains::AC;
        $row['moloni_product_link'] .= $this->company['slug'];
        $row['moloni_product_link'] .= '/productCategories/products/all/';
        $row['moloni_product_link'] .= $row['moloni_product_array']['productId'];
    }

    private function createWcLink(array &$row)
    {
        $wcProductId = $row['wc_product_object']->get_id();

        $row['wc_product_link'] = admin_url("post.php?post=$wcProductId&action=edit");
    }

    /**
     * Fetch Moloni product
     *
     * @throws APIExeption
     */
    private function findMoloniProduct(WC_Product $wcProduct)
    {
        /** Fetch by our associations table */

        $association = ProductAssociations::findByWcId($wcProduct->get_id());

        if (!empty($association)) {
            $byId = Products::queryProduct(['productId' => (int)$association['ml_product_id']]);
            $byId = $byId['data']['product']['data'] ?? [];

            if (!empty($byId)) {
                return $byId;
            }

            ProductAssociations::deleteById($association['id']);
        }

        $wcSku = $wcProduct->get_sku();

        if (empty($wcSku)) {
            return [];
        }

        $variables = [
            'options' => [
                'filter' => [
                    [
                        'field' => 'reference',
                        'comparison' => 'eq',
                        'value' => $wcSku,
                    ],
                    [
                        'field' => 'visible',
                        'comparison' => 'in',
                        'value' => '[0, 1]'
                    ]
                ],
                "includeVariants" => true
            ]
        ];

        $query = Products::queryProducts($variables);

        $byReference = $query['data']['products']['data'] ?? [];

        if (!empty($byReference) && isset($byReference[0]['productId'])) {
            return $byReference[0];
        }

        return [];
    }
}
