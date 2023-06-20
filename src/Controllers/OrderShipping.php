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
    private $unit_id;
    private $has_stock = 0;

    private $hasIVA = false;
    private $fiscalZone;

    /**
     * OrderProduct constructor.
     *
     * @param WC_Order $order
     * @param int|null $index
     * @param string|null $fiscalZone
     */
    public function __construct(WC_Order $order, ?int $index = 0, ?string $fiscalZone = 'es')
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

        if ((int)$moloniTax['type'] === 1) {
            $this->hasIVA = true;
        }

        return $tax;
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
}
