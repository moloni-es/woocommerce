<?php

namespace MoloniES\Controllers;

use WC_Order;
use MoloniES\Exceptions\HelperException;
use MoloniES\Services\MoloniProduct\Helpers\GetOrCreateCategory;
use MoloniES\API\Products;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\DocumentError;
use MoloniES\Tools;

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
    private $exemption_reason = '';

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
    private $unit_id;
    private $has_stock = 0;

    private $fiscalZone;

    /**
     * OrderProduct constructor.
     *
     * @param WC_Order $order
     * @param int|null $index
     * @param array|null $fiscalZone
     */
    public function __construct(WC_Order $order, ?int $index = 0, ?array $fiscalZone = [])
    {
        $this->order = $order;
        $this->index = $index;
        $this->fiscalZone = $fiscalZone;
    }

    /**
     * Create shipping
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function create(): OrderShipping
    {
        $this
            ->setQuantity()
            ->setPrice()
            ->setName()
            ->setReference()
            ->setDiscount()
            ->setTaxes()
            ->setSummary()
            ->setProductId();

        return $this;
    }

    //            Sets            //

    private function setQuantity(): OrderShipping
    {
        $this->qty = 1;

        return $this;
    }

    private function setPrice(): OrderShipping
    {
        $price = (float)$this->order->get_shipping_total();

        $refundedValue = (float)$this->order->get_total_shipping_refunded();

        if ($refundedValue > 0) {
            $price -= $refundedValue;
        }

        if ($price < 0) {
            $price = 0;
        }

        $this->price = $price;

        return $this;
    }

    private function setName(): OrderShipping
    {
        $name = $this->order->get_shipping_method();

        $this->name = apply_filters('moloni_es_after_order_shipping_setName', $name, $this->order);

        return $this;
    }

    private function setSummary(?string $summary = ''): OrderShipping
    {
        $summary = empty($summary) ? '' : $summary;

        $this->summary = apply_filters('moloni_es_after_order_shipping_setSummary', $summary, $this->order);

        return $this;
    }

    private function setReference(): OrderShipping
    {
        $this->reference = __('Shipping','moloni_es');

        return $this;
    }

    /**
     * Set shipping product
     *
     * @return void
     *
     * @throws DocumentError
     */
    private function setProductId(): void
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
                        'comparison' => 'in',
                        'value' => '[0, 1]'
                    ]
                ],
                "includeVariants" => true
            ]
        ];

        try {
            $query = Products::queryProducts($variables);
        } catch (APIExeption $e) {
            throw new DocumentError(__('Error getting shipping', 'moloni_es'));
        }

        $searchProduct = $query['data']['products']['data'] ?? [];

        if (!empty($searchProduct) && isset($searchProduct[0]['productId'])) {
            $this->product_id = $searchProduct[0]['productId'];
            return;
        }

        // Let's create the shipping product
        $this
            ->setCategory()
            ->setUnitId();

        try {
            $mutation = (Products::mutationProductCreate($this->mapPropsToValues(true)))['data']['productCreate']['data'] ?? [];
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error inserting shipping','moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData(),
                ]
            );
        }


        if (isset($mutation['productId'])) {
            $this->product_id = $mutation['productId'];
            return;
        }

        throw new DocumentError(
            __('Error inserting shipping','moloni_es'),
            [
                'mutation' => $mutation
            ]
        );
    }

    /**
     * Set document catefory
     *
     * @throws DocumentError
     */
    private function setCategory(): OrderShipping
    {
        try {
            $this->category_id = (new GetOrCreateCategory(__('Online Store', 'moloni_es')))->get();
        } catch (HelperException $e) {
            throw new DocumentError($e->getMessage(), $e->getData());
        }

        return $this;
    }

    /**
     * Set unit ID
     *
     * @return void
     *
     * @throws DocumentError
     */
    private function setUnitId(): void
    {
        if (defined('MEASURE_UNIT')) {
            $this->unit_id = (int)MEASURE_UNIT;
        } else {
            throw new DocumentError(__('Measure unit not set!','moloni_es'));
        }
    }

    /**
     * Set the discount in percentage
     *
     * @return $this
     */
    private function setDiscount(): OrderShipping
    {
        $this->discount = $this->price <= 0 ? 100 : 0;

        return $this;
    }

    /**
     * Set the taxes of a product
     *
     * @throws DocumentError
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

            return $this;
        }

        if ($this->isCountryIntraCommunity()) {
            $this->exemption_reason = defined('EXEMPTION_REASON_SHIPPING') ? EXEMPTION_REASON_SHIPPING : '';
        } else {
            $this->exemption_reason = defined('EXEMPTION_REASON_SHIPPING_EXTRA_COMMUNITY') ? EXEMPTION_REASON_SHIPPING_EXTRA_COMMUNITY : '';
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
        $tax['value'] = (float)$moloniTax['value'];
        $tax['ordering'] = count($this->taxes) + 1;
        $tax['cumulative'] = false;

        return $tax;
    }

    //            Gets            //

    public function getPrice(): float
    {
        return $this->price ?? 0.0;
    }

    /**
     * To array
     *
     * @param bool|null $toInsert
     *
     * @return array
     */
    public function mapPropsToValues(?bool $toInsert = false): array
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

    //          Auxiliary          //

    /**
     * Check if country is intra community
     *
     * @return bool
     */
    private function isCountryIntraCommunity(): bool
    {
        if (!isset(Tools::$europeanCountryCodes[$this->fiscalZone['code']])) {
            return false;
        }

        if ($this->fiscalZone['code'] === 'ES' && in_array($this->fiscalZone['state'], ['TF', 'GC'])) {
            return false;
        }

        return true;
    }
}
