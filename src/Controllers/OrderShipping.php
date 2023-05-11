<?php

namespace MoloniES\Controllers;

use MoloniES\API\Products;
use MoloniES\API\Taxes;
use MoloniES\Exceptions\Error;
use MoloniES\Tools;
use WC_Order;

class OrderShipping
{

    /** @var int */
    public $product_id = 0;

    /** @var int */
    private $index;

    /** @var array */
    private $taxes = [];

    /** @var float */
    private $qty;

    /** @var float */
    private $price;

    /** @var string */
    private $exemption_reason;

    /** @var string */
    private $name;

    /** @var float */
    private $discount;

    /** @var WC_Order */
    private $order;

    /** @var string */
    private $reference;

    /** @var int */
    private $category_id;

    private $type = 2;
    private $summary = '';
    private $ean = '';
    private $unit_id;
    private $has_stock = 0;

    private $hasIVA = false;
    private $fiscalZone;

    /**
     * OrderProduct constructor.
     * @param WC_Order $order
     * @param int $index
     */
    public function __construct($order, $index = 0, $fiscalZone = 'es')
    {
        $this->order = $order;
        $this->index = $index;
        $this->fiscalZone = $fiscalZone;
    }

    /**
     * @return $this
     * @throws Error
     */
    public function create()
    {
        $this->qty = 1;
        $this->price = (float)$this->order->get_shipping_total();
        $this->name = $this->order->get_shipping_method();

        $this
            ->setReference()
            ->setDiscount()
            ->setTaxes()
            ->setProductId();

        return $this;
    }

    /**
     * @return $this
     */
    private function setReference()
    {
        $this->reference = __('Shipping','moloni_es');
        return $this;
    }

    /**
     * @return $this
     * @throws Error
     */
    private function setProductId()
    {
        $variables = [
            'options' => [
                'filter' => [
                    [
                        'field' => 'reference',
                        'comparison' => 'eq',
                        'value' => $this->reference,
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

        $searchProduct = Products::queryProducts($variables);
        if (!empty($searchProduct) && isset($searchProduct[0]['productId'])) {
            $this->product_id = $searchProduct[0]['productId'];
            return $this;
        }

        // Lets create the shipping product
        $this
            ->setCategory()
            ->setUnitId();

        $insert = (Products::mutationProductCreate($this->mapPropsToValues(true)))['data']['productCreate']['data'];
        if (isset($insert['productId'])) {
            $this->product_id = $insert['productId'];
            return $this;
        }

        throw new Error(__('Error inserting shipping','moloni_es'));
    }

    /**
     * @throws Error
     */
    private function setCategory()
    {
        $categoryName = __('Online Store','moloni_es');

        $categoryObj = new ProductCategory($categoryName);
        if (!$categoryObj->loadByName()) {
            $categoryObj->create();
        }

        $this->category_id = $categoryObj->category_id;

        return $this;
    }

    /**
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
     * Set the discount in percentage
     * @return $this
     */
    private function setDiscount()
    {
        $this->discount = $this->price <= 0 ? 100 : 0;

        return $this;
    }

    /**
     * Set the taxes of a product
     *
     * @throws Error
     */
    private function setTaxes(): OrderShipping
    {
        $shippingTotal = 0;

        foreach ($this->order->get_shipping_methods() as $item) {
            $taxes = $item->get_taxes();

            foreach ($taxes['total'] as $tax) {
                $shippingTotal += (float)$tax;
            }
        }

        //Normal way
        $taxRate = round(($shippingTotal * 100) / $this->order->get_shipping_total());

        if ($taxRate > 0) {
            $this->taxes[] = $this->setTax($taxRate);
        }

        if (!$this->hasIVA) {
            $this->exemption_reason = defined('EXEMPTION_REASON_SHIPPING') ? EXEMPTION_REASON_SHIPPING : '';
            $this->taxes = [];
        } else {
            $this->exemption_reason = '';
        }

        return $this;
    }

    /**
     * @param float $taxRate Tax Rate in percentage
     * @return array
     * @throws Error
     */
    private function setTax(float $taxRate)
    {
        $moloniTax = Tools::getTaxFromRate((float)$taxRate, $this->fiscalZone);

        $tax = [];
        $tax['taxId'] = (int)$moloniTax['taxId'];
        $tax['value'] = (float)$moloniTax['value'];
        $tax['ordering'] = count($this->taxes) + 1;
        $tax['cumulative'] = false;

        if ((int)$moloniTax['type'] === 1) {
            $this->hasIVA = true;
        }

        return $tax;
    }

    /**
     * @param bool $toInsert
     * @return array
     */
    public function mapPropsToValues($toInsert = false)
    {
        $variables = [
            'productId' => (int) $this->product_id,
            'name' => $this->name,
            'qty' => (float) $this->qty,
            'discount' => (float) $this->discount,
            'ordering' => (int) $this->index,
            'exemptionReason' => $this->exemption_reason,
            'taxes' => $this->taxes,
            'price' => (float) $this->price
        ];

        if ($toInsert) {
            unset($variables['productId']);
            unset($variables['qty']);
            unset($variables['discount']);
            unset($variables['ordering']);

            $variables = [
                'data' => $variables
            ];

            $variables['data']['reference'] = $this->reference;
            $variables['data']['type'] = (int) $this->type;
            $variables['data']['hasStock'] = (bool) $this->has_stock;
            $variables['data']['summary'] = $this->summary;
            $variables['data']['measurementUnitId'] = (int) $this->unit_id;
            $variables['data']['productCategoryId'] = (int) $this->category_id;
        }

        return $variables;
    }
}