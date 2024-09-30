<?php

namespace MoloniES\Controllers;

use WC_Tax;
use WC_Order;
use WC_Order_Item_Product;
use MoloniES\Tools;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\DocumentError;
use MoloniES\Exceptions\HelperException;
use MoloniES\Controllers\Helpers\GetMoloniProductFromOrder;

class OrderProduct
{

    /** @var int */
    private $product_id = 0;

    /** @var int */
    private $order;

    /**
     * @var WC_Order_Item_Product
     */
    private $orderProduct;

    /** @var WC_Order */
    private $wc_order;

    /** @var array */
    private $taxes = [];

    /** @var float|int */
    private $qty;

    /** @var float */
    private $price;

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

    private $fiscalZone;

    /**
     * OrderProduct constructor.
     *
     * @param WC_Order_Item_Product $product
     * @param WC_Order $wcOrder
     * @param int|null $order
     * @param array|null $fiscalZone
     */
    public function __construct(WC_Order_Item_Product $product, WC_Order $wcOrder, ?int $order = 0, ?array $fiscalZone = [])
    {
        $this->orderProduct = $product;
        $this->wc_order = $wcOrder;
        $this->order = $order;
        $this->fiscalZone = $fiscalZone;
    }

    //          Publics          //

    /**
     * Create product
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function create(): OrderProduct
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

    public function mapPropsToValues(): array
    {
        $props = [
            'productId' => (int)$this->product_id,
            'name' => $this->name,
            'price' => (float)$this->price,
            'summary' => $this->summary,
            'ordering' => $this->order,
            'qty' => (float)$this->qty,
            'discount' => (float)$this->discount,
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

    //          Gets          //

    public function getQty()
    {
        return $this->qty;
    }

    //          Sets          //

    private function setName(?string $name = ''): OrderProduct
    {
        $name = apply_filters('moloni_es_before_order_item_setName', $name, $this->orderProduct);

        if (empty($name)) {
            $name = $this->orderProduct->get_name();
        }

        $this->name = apply_filters('moloni_es_after_order_item_setName', $name, $this->orderProduct);

        return $this;
    }

    /**
     * Set summary
     *
     * @return $this
     */
    private function setSummary(?string $summary = ''): OrderProduct
    {
        $summary = apply_filters('moloni_es_before_order_item_setSummary', $summary, $this->orderProduct);

        if (empty($summary)) {
            $variationAttributes = $this->getSummaryVariationAttributes();
            $extraOptions = $this->getSummaryExtraProductOptions();

            switch (true) {
                case !empty($variationAttributes) && !empty($extraOptions):
                    $summary = $variationAttributes . '\n' . $extraOptions;

                    break;
                case !empty($variationAttributes):
                    $summary = $variationAttributes;

                    break;
                case !empty($extraOptions):
                    $summary = $extraOptions;

                    break;
                default:
                    $summary = '';

                    break;
            }
        }

        $this->summary = apply_filters('moloni_es_after_order_item_setSummary', $summary, $this->orderProduct);

        return $this;
    }

    /**
     * Set variation summary
     *
     * @return string
     */
    private function getSummaryVariationAttributes(): string
    {
        $summary = '';

        if ($this->orderProduct->get_variation_id() > 0) {
            $product = wc_get_product($this->orderProduct->get_variation_id());
            $attributes = $product->get_attributes();

            if (is_array($attributes) && !empty($attributes)) {
                $summary = wc_get_formatted_variation($attributes, true);
            }
        }

        return $summary;
    }

    /**
     * Set extra summary
     *
     * @return string
     */
    private function getSummaryExtraProductOptions(): string
    {
        $summary = '';
        $checkEPO = $this->orderProduct->get_meta('_tmcartepo_data', true);
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
     * Set price
     *
     * @return OrderProduct
     */
    private function setPrice(): OrderProduct
    {
        $price = 0;

        if ($this->qty > 0) {
            $price = (float)$this->orderProduct->get_subtotal() / $this->qty;
            $refundedValue = $this->wc_order->get_total_refunded_for_item($this->orderProduct->get_id());

            if ($refundedValue !== 0) {
                $refundedValue /= $this->qty;

                $price -= $refundedValue;
            }

            if ($price < 0) {
                $price = 0;
            }
        }

        $this->price = $price;

        return $this;
    }

    /**
     * Set quantity
     *
     * @return OrderProduct
     */
    private function setQty(): OrderProduct
    {
        $this->qty = (float)$this->orderProduct->get_quantity();
        $refundedQty = absint($this->wc_order->get_qty_refunded_for_item($this->orderProduct->get_id()));

        if ($refundedQty !== 0) {
            $this->qty -= $refundedQty;
        }

        return $this;
    }

    /**
     * Fetch product from Moloni
     *
     * @return $this
     *
     * @throws DocumentError
     */
    private function setProductId(): OrderProduct
    {
        try {
            $this->product_id = (new GetMoloniProductFromOrder($this->orderProduct))->handle();
        } catch (HelperException $e) {
            throw new DocumentError($e->getMessage(), $e->getData());
        }

        return $this;
    }

    /**
     * Set the discount in percentage
     *
     * @return $this
     */
    private function setDiscount(): OrderProduct
    {
        $total = (float)$this->orderProduct->get_total();
        $subTotal = (float)$this->orderProduct->get_subtotal();

        if ((int)$subTotal !== 0) {
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
     * @throws DocumentError
     */
    private function setTaxes(): OrderProduct
    {
        $taxes = $this->orderProduct->get_taxes();

        foreach ($taxes['subtotal'] as $taxId => $value) {
            if (!empty($value) || empty($this->price)) {
                $taxRate = preg_replace('/[^0-9.]/', '', WC_Tax::get_rate_percent($taxId));

                if ((float)$taxRate > 0) {
                    $this->taxes[] = $this->setTax($taxRate);
                }
            }
        }

        if (empty($this->taxes)) {
            $this->exemption_reason = defined('EXEMPTION_REASON') ? EXEMPTION_REASON : '';
        } else {
            $this->exemption_reason = '';
        }

        return $this;
    }

    /**
     * Set product tax
     *
     * @param float|int|null $taxRate Tax Rate in percentage
     *
     * @return array
     *
     * @throws DocumentError
     */
    private function setTax($taxRate): array
    {
        try {
            $moloniTax = Tools::getTaxFromRate((float)$taxRate, $this->fiscalZone);
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error fetching taxes', 'moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        }

        $tax = [];
        $tax['taxId'] = (int)$moloniTax['taxId'];
        $tax['value'] = (float)$taxRate;
        $tax['ordering'] = count($this->taxes) + 1;
        $tax['cumulative'] = false;

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
}
