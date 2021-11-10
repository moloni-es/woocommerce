<?php

namespace MoloniES\Controllers;

use MoloniES\API\Products;
use MoloniES\API\Taxes;
use MoloniES\Error;
use MoloniES\Tools;
use WC_Order_Item_Fee;

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
    private $exemption_reason;

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

    private $hasIVA = false;
    private $fiscalZone;

    /**
     * OrderProduct constructor.
     *
     * @param WC_Order_Item_Fee $fee
     * @param int $index
     */
    public function __construct($fee, $index = 0, $fiscalZone = 'es')
    {
        $this->fee = $fee;
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
    private function setReference()
    {
        $this->reference = __('Fee','moloni_es');
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

        throw new Error(__('Error inserting order fees' , 'moloni_es'));
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
        $this->discount = $this->discount < 0 ? 0 : ($this->discount > 100) ? 100 : $this->discount;

        return $this;
    }

    /**
     * Set the taxes of a product
     * @throws Error
     */
    private function setTaxes()
    {
        // If a tax is set in settings (should not be used by the client)
        if(defined('TAX_ID') && TAX_ID > 0) {
            $variables = [
                'companyId' => (int) MOLONIES_COMPANY_ID,
                'taxId' => (int) TAX_ID
            ];

            $query = (Taxes::queryTax($variables))['data']['tax']['data'];

            $tax['taxId'] = (int)$query['taxId'];
            $tax['value'] = (float)$query['value'];
            $tax['ordering'] = 1;
            $tax['cumulative'] = false;

            $unitPrice = (float)$this->price + (float)$this->fee->get_total_tax();

            $this->price = ($unitPrice * 100);
            $this->price /= (100 + $tax['value']);

            $this->taxes = $tax;
            $this->exemption_reason = '';

            return $this;
        }

        //Normal way
        $taxedArray = $this->fee->get_taxes();
        $taxedValue = 0;
        $taxRate = 0;

        if (isset($taxedArray['total']) && count($taxedArray['total']) > 0) {
            foreach ($taxedArray['total'] as $value) {
                $taxedValue += $value;
            }

            $taxRate = round(($taxedValue * 100) / (float)$this->fee->get_amount());
        }

        if ($taxRate > 0) {
            $this->taxes[] = $this->setTax($taxRate);
        }

        if (!$this->hasIVA) {
            $this->exemption_reason = defined('EXEMPTION_REASON_SHIPPING') ? EXEMPTION_REASON_SHIPPING : '';
            $this->taxes=[];
        }else {
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
        $tax['taxId'] = (int)$moloniTax['taxId'];
        $tax['value'] = (float)$moloniTax['value'];
        $tax['ordering'] = count($this->taxes) + 1;
        $tax['cumulative'] = false;

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