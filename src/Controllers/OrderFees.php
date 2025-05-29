<?php

namespace MoloniES\Controllers;

use WC_Order_Item_Fee;
use MoloniES\Exceptions\HelperException;
use MoloniES\Services\MoloniProduct\Helpers\GetOrCreateCategory;
use MoloniES\Tools;
use MoloniES\API\Products;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\DocumentError;

class OrderFees
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

    /** @var WC_Order_Item_Fee */
    private $fee;

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
     * @param WC_Order_Item_Fee $fee
     * @param int|null $index
     * @param array|null $fiscalZone
     */
    public function __construct(WC_Order_Item_Fee $fee, ?int $index = 0, ?array $fiscalZone = [])
    {
        $this->fee = $fee;
        $this->index = $index;
        $this->fiscalZone = $fiscalZone;
    }

    /**
     * Create order fee
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function create(): OrderFees
    {
        $this->qty = 1;
        $this->price = (float)$this->fee['line_total'];

        $feeName = $this->fee->get_name();
        $this->name = !empty($feeName) ? $feeName : __('Fee','moloni_es');

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
    private function setReference(): OrderFees
    {
        $this->reference = __('Fee','moloni_es');

        return $this;
    }

    /**
     * Set product id
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
            throw new DocumentError(
                __('Error fetching order fee', 'moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
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
            $insert = (Products::mutationProductCreate($this->mapPropsToValues(true)))['data']['productCreate']['data'] ?? [];
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error creating order fee', 'moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        }

        if (isset($insert['productId'])) {
            $this->product_id = $insert['productId'];
            return;
        }

        throw new DocumentError(__('Error inserting order fees' , 'moloni_es'));
    }

    /**
     * Set category
     *
     * @throws DocumentError
     */
    private function setCategory(): OrderFees
    {
        try {
            $this->category_id = (new GetOrCreateCategory(__('Online Store', 'moloni_es')))->get();
        } catch (HelperException $e) {
            throw new DocumentError($e->getMessage(), $e->getData());
        }

        return $this;
    }

    /**
     * Set unit
     *
     * @return void
     *
     * @throws DocumentError
     */
    private function setUnitId(): void
    {
        if (defined('MEASURE_UNIT')) {
            $this->unit_id = MEASURE_UNIT;
        } else {
            throw new DocumentError(__('Measure unit not set!','moloni_es'));
        }

    }

    /**
     * Set the discount in percentage
     *
     * @return $this
     */
    private function setDiscount(): OrderFees
    {
        $this->discount = $this->price <= 0 ? 100 : 0;

        return $this;
    }

    /**
     * Set the taxes of a product
     *
     * @throws DocumentError
     */
    private function setTaxes(): OrderFees
    {
        $taxedArray = $this->fee->get_taxes();
        $taxedValue = 0.0;
        $taxRate = 0.0;

        if (isset($taxedArray['total']) && count($taxedArray['total']) > 0) {
            foreach ($taxedArray['total'] as $value) {
                $taxedValue += (float)$value;
            }

            $taxRate = round(($taxedValue * 100) / (float)$this->fee->get_amount());
        }

        if ($taxRate > 0) {
            $this->taxes[] = $this->setTax($taxRate);

            return $this;
        }

        if ($this->isCountryIntraCommunity()) {
            $this->exemption_reason = defined('EXEMPTION_REASON') ? EXEMPTION_REASON : '';
        } else {
            $this->exemption_reason = defined('EXEMPTION_REASON_EXTRA_COMMUNITY') ? EXEMPTION_REASON_EXTRA_COMMUNITY : '';
        }

        return $this;
    }

    /**
     * Set taxes
     *
     * @param int|float|null $taxRate Tax Rate in percentage
     *
     * @return array
     *
     * @throws DocumentError
     */
    private function setTax($taxRate = 0): array
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
     * Check if the country is intra community
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
