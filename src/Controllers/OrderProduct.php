<?php

namespace MoloniES\Controllers;

use MoloniES\API\Taxes;
use MoloniES\API\Warehouses;
use MoloniES\Exceptions\Error;
use MoloniES\Tools;
use WC_Order;
use WC_Order_Item_Product;
use WC_Tax;

class OrderProduct
{

    /** @var int */
    public $product_id = 0;

    /** @var int */
    private $order;

    /**
     * @var WC_Order_Item_Product
     */
    private $product;

    /** @var WC_Order */
    private $wc_order;

    /** @var array */
    private $taxes = [];

    /** @var float */
    public $qty;

    /** @var float */
    public $price;

    /** @var string */
    private $exemption_reason;

    /** @var string */
    private $name;

    /** @var string */
    private $summary;

    /** @var float */
    private $discount;

    /** @var int */
    private $warehouse_id = 0;

    /** @var bool */
    private $hasIVA = false;
    private $fiscalZone;

    /**
     * OrderProduct constructor.
     * @param WC_Order_Item_Product $product
     * @param WC_Order $wcOrder
     * @param int $order
     */
    public function __construct($product, $wcOrder, $order = 0, $fiscalZone = 'es')
    {
        $this->product = $product;
        $this->wc_order = $wcOrder;
        $this->order = $order;
        $this->fiscalZone = $fiscalZone;
    }

    /**
     * @return $this
     * @throws Error
     */
    public function create()
    {
        $this
            ->setName()
            ->setQty()
            ->setPrice()
            ->setSummary()
            ->setProductId()
            ->setDiscount()
            ->setTaxes()
            ->setWarehouse();

        return $this;
    }

    public function setName()
    {
        $this->name = $this->product->get_name();
        return $this;
    }

    /**
     * @param null|string $summary
     * @return $this
     */
    public function setSummary($summary = null)
    {
        if ($summary) {
            $this->summary = $summary;
        } else {
            $this->summary .= $this->getSummaryVariationAttributes();

            if (!empty($this->summary)) {
                $this->summary .= "\n";
            }

            $this->summary .= $this->getSummaryExtraProductOptions();
        }

        return $this;
    }

    /**
     * @return string
     */
    private function getSummaryVariationAttributes()
    {
        $summary = '';

        if ($this->product->get_variation_id() > 0) {
            $product = wc_get_product($this->product->get_variation_id());
            $attributes = $product->get_attributes();
            if (is_array($attributes) && !empty($attributes)) {
                $summary = wc_get_formatted_variation($attributes, true);
            }
        }

        return $summary;
    }

    /**
     * @return string
     */
    private function getSummaryExtraProductOptions()
    {
        $summary = '';
        $checkEPO = $this->product->get_meta('_tmcartepo_data', true);
        $extraProductOptions = maybe_unserialize($checkEPO);

        if ($extraProductOptions && is_array($extraProductOptions)) {
            foreach ($extraProductOptions as $extraProductOption) {
                if (isset($extraProductOption['name'], $extraProductOption['value'])) {

                    if (!empty($summary)) {
                        $summary .= "\n";
                    }

                    $summary .= $extraProductOption['name'] . ' ' . $extraProductOption['value'];
                }
            }
        }

        return $summary;
    }

    /**
     * @return OrderProduct
     */
    public function setPrice()
    {
        $this->price = (float)$this->product->get_subtotal() / $this->qty;
        $refundedValue = $this->wc_order->get_total_refunded_for_item($this->product->get_id());

        if ($refundedValue !== 0) {
            $refundedValue /= $this->qty;

            $this->price -= $refundedValue;
        }

        if ($this->price < 0) {
            $this->price = 0;
        }

        return $this;
    }

    /**
     * @return OrderProduct
     */
    public function setQty()
    {
        $this->qty = (float)$this->product->get_quantity();
        $refundedQty = absint($this->wc_order->get_qty_refunded_for_item($this->product->get_id()));

        if ($refundedQty !== 0) {
            $this->qty -= $refundedQty;
        }

        return $this;
    }

    /**
     * @return $this
     * @throws Error
     */
    private function setProductId()
    {
        $wooCommerceProduct = $this->product->get_product();

        if (empty($wooCommerceProduct)) {
            throw new Error(__('Order products were deleted.','moloni_es'));
        }

        $product = new Product($wooCommerceProduct);

        if (!$product->loadByReference()) {
            $product->create();
        }

        $this->product_id = $product->getProductId();

        return $this;
    }

    /**
     * Set the discount in percentage
     * @return $this
     */
    private function setDiscount()
    {
        $total = (float)$this->product->get_total();
        $subTotal = (float)$this->product->get_subtotal();

        if ($subTotal !== (float)0) {
            $this->discount = (100 - (($total * 100) / $subTotal));
        }

        if ($this->discount > 100) {
            $this->discount = 100;
        }

        if (empty($this->discount) || $this->discount < 0) {
            $this->discount = 0;
        }

        return $this;
    }

    /**
     * Set the taxes of a product
     *
     * @throws Error
     */
    private function setTaxes(): OrderProduct
    {
        $taxes = $this->product->get_taxes();

        foreach ($taxes['subtotal'] as $taxId => $value) {
            if (!empty($value)) {
                $taxRate = preg_replace('/[^0-9.]/', '', WC_Tax::get_rate_percent($taxId));

                if ((float)$taxRate > 0) {
                    $this->taxes[] = $this->setTax($taxRate);
                }
            }
        }

        if (!$this->hasIVA) {
            $this->exemption_reason = defined('EXEMPTION_REASON') ? EXEMPTION_REASON : '';
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
    private function setTax($taxRate)
    {
        $moloniTax = Tools::getTaxFromRate((float)$taxRate, $this->fiscalZone);

        $tax = [];
        $tax['taxId'] = (int) $moloniTax['taxId'];
        $tax['value'] = (float) $taxRate;
        $tax['ordering'] = count($this->taxes) + 1;
        $tax['cumulative'] = (bool) 0;

        if ((int) $moloniTax['type'] === 1) {
            $this->hasIVA = true;
        }

        return $tax;
    }

    /**
     * Set order product warehouse
     *
     * @return void
     */
    private function setWarehouse(): void
    {
        if (defined('MOLONI_PRODUCT_WAREHOUSE') && (int)MOLONI_PRODUCT_WAREHOUSE > 0) {
            $this->warehouse_id = (int)MOLONI_PRODUCT_WAREHOUSE;
        }
    }

    public function mapPropsToValues(): array
    {
        $props = [
            'productId' => (int) $this->product_id,
            'name' => $this->name,
            'price' => (float) $this->price,
            'summary' => $this->summary,
            'ordering' => $this->order,
            'qty' => (float) $this->qty,
            'discount' => (float) $this->discount,
            'exemptionReason' => '',
            'taxes' => [],
        ];

        if (!empty($this->warehouse_id)) {
            $props['warehouseId'] = $this->warehouse_id;
        }

        if (!empty($this->taxes)) {
            $props['taxes'] = $this->taxes;
        }

        if (!empty($this->exemption_reason)) {
            $props['exemptionReason'] = $this->exemption_reason;
        }

        return $props;
    }
}