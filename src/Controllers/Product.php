<?php

namespace MoloniES\Controllers;

use MoloniES\API\Products as ApiProducts;
use MoloniES\API\Taxes;
use MoloniES\API\Warehouses;
use MoloniES\Error;
use MoloniES\Log;
use MoloniES\Tools;
use WC_Product;
use WC_Tax;

/**
 * Class Product
 * @package Moloni\Controllers
 */
class Product
{

    /** @var WC_Product */
    private $product;

    public $product_id;
    public $category_id;
    private $type;
    public $reference;
    public $name;
    private $summary = '';
    private $ean = '';
    public $price;
    private $unit_id;
    public $has_stock;
    public $stock;
    private $exemption_reason;
    private $taxes;
    private $warehouseId;

    /**
     * Product constructor.
     * @param WC_Product $product
     */
    public function __construct($product)
    {
        $this->product = $product;
    }

    /**
     * Loads a product
     * @throws Error
     */
    public function loadByReference()
    {
        $this->setReference();

        $variables = ['companyId' => (int) MOLONIES_COMPANY_ID,
            'options' => [
                'filter' => [
                    'field' => 'reference',
                    'comparison' => 'eq',
                    'value' => $this->reference,
                ],
                "includeVariants" => true
            ]
        ];

        $searchProduct = ApiProducts::queryProducts($variables);

        if (!empty($searchProduct) && isset($searchProduct[0]['productId'])) {
            $product = $searchProduct[0];
            $this->product_id = $product['productId'];
            $this->category_id = $product['productCategory']['productCategoryId'];
            $this->has_stock = $product['hasStock'];
            $this->stock = $product['stock'];
            $this->price = $product['price'];
            $this->warehouseId = $product['warehouse']['warehouseId'];
            return $this;
        }

        return false;
    }

    /**
     * Create a product based on a WooCommerce Product
     * @return $this
     * @throws Error
     */
    public function create()
    {
        $this->setProduct();

        $insert = (ApiProducts::mutationProductCreate($this->mapPropsToValues()))['data']['productCreate']['data'];
        if (isset($insert['productId'])) {
            $this->product_id = $insert['productId'];
            return $this;
        }

        throw new Error(sprintf(__('Error creating product %s','moloni_es') ,$this->name));
    }

    /**
     * Create a product based on a WooCommerce Product
     * @return $this
     * @throws Error
     */
    public function update()
    {
        $this->setProduct();

        $update = (ApiProducts::mutationProductUpdate($this->mapPropsToValues()));

        $update = $update['data']['productUpdate']['data'];
        if (isset($update['productId'])) {
            $this->product_id = $update['productId'];
            return $this;
        }

        throw new Error(sprintf(__('Error updating product %s','moloni_es') ,$this->name));
    }

    /**
     * Instantiates all the product needed properties
     * @throws Error
     */
    private function setProduct()
    {
        $this
            ->setReference()
            ->setCategory()
            ->setType()
            ->setName()
            ->setPrice()
            ->setEan()
            ->setUnitId()
            ->setWarehouse()
            ->setTaxes();
    }

    /**
     * Returns product id
     * @return bool|int
     */
    public function getProductId()
    {
        return $this->product_id ?: false;
    }

    /**
     * Sets product reference
     * @return $this
     */
    private function setReference()
    {
        $this->reference = $this->product->get_sku();

        if (empty($this->reference)) {
            $this->reference = Tools::createReferenceFromString($this->product->get_name());
        }

        return $this;
    }

    /**
     * Sets category
     * @throws Error
     */
    private function setCategory()
    {

        $categories = $this->product->get_category_ids();

        // Get the deepest category from all the trees
        if (!empty($categories) && is_array($categories)) {
            $categoryTree = [];

            foreach ($categories as $category) {
                $parents = get_ancestors($category, 'product_cat');
                $parents = array_reverse($parents);
                $parents[] = $category;

                if (is_array($parents) && count($parents) > count($categoryTree)) {
                    $categoryTree = $parents;
                }
            }

            $this->category_id = 0;
            foreach ($categoryTree as $categoryId) {
                $category = get_term_by('id', $categoryId, 'product_cat');
                if (!empty($category->name)) {
                    $categoryObj = new ProductCategory($category->name, $this->category_id);

                    if (!$categoryObj->loadByName()) {
                        $categoryObj->create();
                    }

                    $this->category_id = $categoryObj->category_id;
                }
            }
        }

        if ((int)$this->category_id === 0) {
            $categoryName = __('Online Store','moloni_es');
            $categoryObj = new ProductCategory($categoryName, 0);

            if (!$categoryObj->loadByName()) {
                $categoryObj->create();
            }

            $this->category_id = $categoryObj->category_id;
        }

        return $this;
    }

    /**
     * Sets type
     * @return $this
     */
    private function setType()
    {
        // 1 - Product, 2 - Service, 3 - Other
        // If the product is virtual or downloadable then its a service
        if ($this->product->is_virtual() || $this->product->is_downloadable()) {
            $this->type = 2;
            $this->has_stock = 0;
        } else {
            $this->type = 1;
            $this->has_stock = 1;
            $this->stock = (float)$this->product->get_stock_quantity();
        }

        return $this;
    }

    /**
     * Set the name of the product
     * @return $this
     */
    private function setName()
    {
        $this->name = $this->product->get_name();
        return $this;
    }

    /**
     * Set the price of the product
     * @return $this
     */
    private function setPrice()
    {
        $this->price = (float)wc_get_price_excluding_tax($this->product);
        return $this;
    }

    /**
     * Sets EAN
     * @return $this
     */
    private function setEan()
    {
        $metaBarcode = $this->product->get_meta('barcode', true);
        if (!empty($metaBarcode)) {
            $this->ean = $metaBarcode;
        }

        return $this;
    }

    /**
     * Sets measurement unit
     * @return $this
     * @throws Error
     */
    private function setUnitId()
    {
        if (defined('MEASURE_UNIT')) {
            $this->unit_id = MEASURE_UNIT;
        } else {
            throw new Error(__('Measure unit not set!','moloni_es'));
        }

        return $this;
    }

    /**
     * Sets the taxes of a product or its exemption reason
     * @return $this
     * @throws Error
     */
    private function setTaxes()
    {
        //if a tax is set in settings (should not be used by the client)
        if(defined('TAX_ID') && TAX_ID > 0) {

            $variables = [
                'companyId' => (int) MOLONIES_COMPANY_ID,
                'taxId' => (int) TAX_ID
            ];

            $query = (Taxes::queryTax($variables))['data']['tax']['data'];

            $tax['taxId'] = (int) $query['taxId'];
            $tax['value'] = (float) $query['value'];
            $tax['ordering'] = 1;
            $tax['cumulative'] = false;

            $this->price = (($this->product->get_total) * 100);
            $this->price = $this->price / (100 + $tax['value']);

            $this->taxes = $tax;
            $this->exemption_reason = '';

            return $this;
        }

        //normal set of taxes
        $hasIVA = false;

        if ($this->product->get_tax_status() === 'taxable') {
            // Get taxes based on a tax class of a product
            // If the tax class is empty it means the products uses the shop default
            $productTaxes = $this->product->get_tax_class();
            $taxRates = WC_Tax::get_base_tax_rates($productTaxes);

            foreach ($taxRates as $order => $taxRate) {
                $moloniTax = Tools::getTaxFromRate((float)$taxRate['rate']);

                if (!$moloniTax) {
                    continue;
                }

                $tax = [];
                $tax['taxId'] = (int) $moloniTax['taxId'];
                $tax['value'] = (float) $taxRate['rate'];
                $tax['ordering'] = (int) $order;
                $tax['cumulative'] = false;

                if ((float)$taxRate['rate'] > 0) {
                    $this->taxes[] = $tax;
                }

                if ((int)$moloniTax['type'] === 1) {
                    $hasIVA = true;
                }
            }
        }

        if (!$hasIVA) {
            $this->exemption_reason = defined('EXEMPTION_REASON') ? EXEMPTION_REASON : '';
            $this->taxes=[];
        } else {
            $this->exemption_reason = '';
        }

        return $this;
    }

    /**
     * Sets product warehouse
     * @return $this
     * @throws Error
     */
    private function setWarehouse()
    {
        if (!empty($this->warehouseId)) {
            return $this;
        }

        if (defined('MOLONI_PRODUCT_WAREHOUSE') && (int) MOLONI_PRODUCT_WAREHOUSE > 0) {
            $this->warehouseId = (int) MOLONI_PRODUCT_WAREHOUSE;
        } else {
            $variables = [
                'companyId' => (int) MOLONIES_COMPANY_ID,
            ];

            $results = Warehouses::queryWarehouses($variables);

            $this->warehouseId = $results[0]['warehouseId']; //fail safe
            foreach ($results as $result) {
                if ((bool) $result['isDefault'] === true) {
                    $this->warehouseId = $result['warehouseId'];
                }
            }
        }

        return $this;
    }

    /**
     * Map this object properties to an array to insert/update a moloni document
     * @return array
     */
    private function mapPropsToValues()
    {
        $variables = [
            'companyId' => (int) MOLONIES_COMPANY_ID,
            'data' => [
                'productCategoryId' => (int)$this->category_id,
                'type' => (int)$this->type,
                'reference' => $this->reference,
                'name' => $this->name,
                'measurementUnitId' => (int)$this->unit_id,
                'price' => $this->price,
                'summary' => $this->summary,
                'exemptionReason' => $this->exemption_reason,
                'hasStock' => (bool)$this->has_stock,
                'taxes' => $this->taxes
            ],
        ];

        if(!empty($this->product_id)) {
            $variables['data']['productId'] = (int)$this->product_id;
        } else {
            if ((bool) $this->has_stock === true) {
                $variables['data']['warehouseId'] = (int) $this->warehouseId;
                $variables['data']['warehouses'] = [
                    'warehouseId' => (int) $this->warehouseId,
                    'stock' => (float) $this->stock
                ];
            }
        }

        return $variables;
    }
}