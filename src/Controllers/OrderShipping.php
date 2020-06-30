<?php

namespace MoloniES\Controllers;

use MoloniES\API\Products;
use MoloniES\API\Taxes;
use MoloniES\Error;
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

    /**
     * OrderProduct constructor.
     * @param WC_Order $order
     * @param int $index
     */
    public function __construct($order, $index = 0)
    {
        $this->order = $order;
        $this->index = $index;
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
        $this->reference = 'Envío';
        return $this;
    }

    /**
     * @return $this
     * @throws Error
     */
    private function setProductId()
    {
        $variables = ['companyId' => (int) MOLONIES_COMPANY_ID,
            'options' => [
                'filter' => [
                    'field' => 'reference',
                    'comparison' => 'eq',
                    'value' => $this->reference
                ]
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
        $categoryName = 'Loja Online';

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
        $this->discount = $this->discount < 0 ? 0 : $this->discount > 100 ? 100 : $this->discount;

        return $this;
    }

    /**
     * Set the taxes of a product
     * @throws Error
     */
    private function setTaxes()
    {
        $shippingTotal = 0;

        foreach ($this->order->get_shipping_methods() as $item_id => $item) {
            $taxes = $item->get_taxes();
            foreach ($taxes['total'] as $tax_rate_id => $tax) {
                $shippingTotal += (float)$tax;
            }
        }

        //if a tax is set in settings (should not be used by the client)
        if(defined('TAX_ID_SHIPPING') && TAX_ID_SHIPPING > 0) {

            $variables = [
                'companyId' => (int) MOLONIES_COMPANY_ID,
                'taxId' => (int) TAX_ID_SHIPPING
            ];

            $query = (Taxes::queryTax($variables))['data']['tax']['data'];

            $tax = [];
            $tax['taxId'] = (int) $query['taxId'];
            $tax['value'] = (float) $query['value'];
            $tax['ordering'] = 1;
            $tax['cumulative'] = false;

            $this->price = (($this->price + $shippingTotal) * 100);
            $this->price = $this->price / (100 + $tax['value']);

            $this->taxes = $tax;
            $this->exemption_reason = '';

            return $this;
        }

        //Normal way
        $taxRate = round(($shippingTotal * 100) / $this->order->get_shipping_total());

        if ((float)$taxRate > 0) {
            $this->taxes[] = $this->setTax($taxRate);
        }

        if (!$this->hasIVA) {
            $this->exemption_reason = defined('EXEMPTION_REASON_SHIPPING') ? EXEMPTION_REASON_SHIPPING : '';
            $this->taxes=[];
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
        $moloniTax = Tools::getTaxFromRate((float)$taxRate);

        $tax = [];
        $tax['taxId'] = (int) $moloniTax['taxId'];
        $tax['value'] = (float) $taxRate;
        $tax['ordering'] = (int) (count($this->taxes) + 1);
        $tax['cumulative'] = (bool) 0;

        if ((int) $moloniTax['type'] === 1) {
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
                'companyId' => (int) MOLONIES_COMPANY_ID,
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