<?php

namespace MoloniES\Controllers;

use MoloniES\Enums\SyncLogsType;
use MoloniES\Services\MoloniProduct\Helpers\Variants\FindVariant;
use MoloniES\Services\MoloniProduct\Helpers\Variants\GetOrUpdatePropertyGroup;
use MoloniES\Services\MoloniProduct\Update\UpdateVariantProduct;
use MoloniES\Tools\SyncLogs;
use WC_Product;
use WC_Tax;
use WC_Order;
use WC_Order_Item_Product;
use MoloniES\API\Products;
use MoloniES\Exceptions\Error;
use MoloniES\Services\MoloniProduct\Create\CreateSimpleProduct;
use MoloniES\Services\MoloniProduct\Create\CreateVariantProduct;
use MoloniES\Tools;
use MoloniES\Tools\ProductAssociations;

class OrderProduct
{

    /** @var int */
    public $product_id = 0;

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
        $this->orderProduct = $product;
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
        $this->name = $this->orderProduct->get_name();
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
     * @return string
     */
    private function getSummaryExtraProductOptions()
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
     * @return OrderProduct
     */
    public function setPrice()
    {
        $this->price = (float)$this->orderProduct->get_subtotal() / $this->qty;
        $refundedValue = $this->wc_order->get_total_refunded_for_item($this->orderProduct->get_id());

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
     * @throws Error
     */
    private function setProductId(): OrderProduct
    {
        $moloniProduct = [];

        if ($this->orderProduct->get_variation_id() > 0) {
            $association = ProductAssociations::findByWcId($this->orderProduct->get_variation_id());

            /** Association found, let's fetch by ID */
            if (!empty($association)) {
                $moloniProduct = $this->getById($association['ml_product_id']);
            }

            if (empty($moloniProduct)) {
                $moloniProduct = $this->getByProductParent();
            }
        } else {
            $association = ProductAssociations::findByWcId($this->orderProduct->get_product_id());

            /** Association found, let's fetch by ID */
            if (!empty($association)) {
                $moloniProduct = $this->getById($association['ml_product_id']);
            }
        }

        /** Let's search by reference */
        if (empty($moloniProduct)) {
            $wcProduct = $this->orderProduct->get_product();

            if (empty($wcProduct)) {
                throw new Error(__('Order products were deleted.','moloni_es'));
            }

            $moloniProduct = $this->getByReference($wcProduct);

            /** Not found, lets create a new product */
            if (empty($moloniProduct)) {
                $wcProduct = wc_get_product($this->orderProduct->get_product_id());

                if (empty($wcProduct)) {
                    throw new Error(__('Order products were deleted.','moloni_es'));
                }

                if ($wcProduct->is_type('variable')) {
                    $service = new CreateVariantProduct($wcProduct);
                } else {
                    $service = new CreateSimpleProduct($wcProduct);
                }

                $service->run();
                $service->saveLog();
            }
        }

        $this->product_id = $moloniProduct['productId'] ?? 0;

        return $this;
    }

    /**
     * Set the discount in percentage
     * @return $this
     */
    private function setDiscount()
    {
        $total = (float)$this->orderProduct->get_total();
        $subTotal = (float)$this->orderProduct->get_subtotal();

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
        $taxes = $this->orderProduct->get_taxes();

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

    //          REQUESTS          //

    protected function getById(int $productId): array
    {
        $variables = [
            'productId' => $productId
        ];

        $byId = Products::queryProduct($variables);

        return $byId['data']['product']['data'] ?? [];
    }

    protected function getByReference(WC_Product $wcProduct): array
    {
        $reference = $wcProduct->get_sku();

        if (empty($reference)) {
            $reference = Tools::createReferenceFromString($wcProduct->get_name());
        }

        $variables = [
            'options' => [
                'filter' => [
                    [
                        'field' => 'reference',
                        'comparison' => 'eq',
                        'value' => $reference,
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

        $byReference = Products::queryProducts($variables);

        if (!empty($byReference) && isset($byReference[0]['productId'])) {
            return $byReference[0];
        }

        return [];
    }

    protected function getByProductParent(): array
    {
        $wcParentId = $this->orderProduct->get_product_id();
        $wcVariationId = $this->orderProduct->get_variation_id();

        $wcProduct = wc_get_product($wcParentId);

        if (empty($wcProduct)) {
            throw new Error(__('Order products were deleted.','moloni_es'));
        }

        $byReference = $this->getByReference($wcProduct);

        /** Product really does not exist, can return */
        if (empty($byReference)) {
            return [];
        }

        $mlProduct = $byReference[0];

        /** For some reason the prodcut is simple in Moloni, use that one */
        if (empty($mlProduct['variants'])) {
            return $mlProduct;
        }

        $targetId = $mlProduct['propertyGroup']['propertyGroupId'] ?? '';

        $propertyGroup = (new GetOrUpdatePropertyGroup($wcProduct, $targetId))->handle();

        $variant = (new FindVariant(
            $wcProduct->get_id(),
            $this->orderProduct['product_reference'],
            $mlProduct['variants'],
            $propertyGroup['variants'][$wcVariationId] ?? []
        ))->run();

        /** Variant already exists in Moloni, use that one */
        if (!empty($variant)) {
            return $variant;
        }

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcParentId);

        $service = new UpdateVariantProduct($wcProduct, $mlProduct);
        $service->run();
        $service->saveLog();

        $variant = $service->getVariant($wcVariationId);

        if (empty($variant)) {
            throw new Error(__('Could not find variant after update.','moloni_es'));
        }

        return $variant;
    }
}