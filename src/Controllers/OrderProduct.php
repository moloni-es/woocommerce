<?php

namespace MoloniES\Controllers;

use MoloniES\API\Products;
use MoloniES\Enums\SyncLogsType;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\DocumentError;
use MoloniES\Services\MoloniProduct\Create\CreateSimpleProduct;
use MoloniES\Services\MoloniProduct\Create\CreateVariantProduct;
use MoloniES\Services\MoloniProduct\Helpers\Variants\FindVariant;
use MoloniES\Services\MoloniProduct\Helpers\Variants\GetOrUpdatePropertyGroup;
use MoloniES\Services\MoloniProduct\Update\UpdateVariantProduct;
use MoloniES\Tools;
use MoloniES\Tools\ProductAssociations;
use MoloniES\Tools\SyncLogs;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
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
     *
     * @param WC_Order_Item_Product $product
     * @param WC_Order $wcOrder
     * @param int|null $order
     * @param string|null $fiscalZone
     */
    public function __construct(WC_Order_Item_Product $product, WC_Order $wcOrder, ?int $order = 0, ?string $fiscalZone = 'es')
    {
        $this->orderProduct = $product;
        $this->wc_order = $wcOrder;
        $this->order = $order;
        $this->fiscalZone = $fiscalZone;
    }

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

    public function setName(): OrderProduct
    {
        $this->name = $this->orderProduct->get_name();

        return $this;
    }

    /**
     * Set summary
     *
     * @return $this
     */
    public function setSummary(): OrderProduct
    {
        $this->summary .= $this->getSummaryVariationAttributes();

        if (!empty($this->summary)) {
            $this->summary .= "\n";
        }

        $this->summary .= $this->getSummaryExtraProductOptions();

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
    public function setPrice(): OrderProduct
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
     * Set quantity
     *
     * @return OrderProduct
     */
    public function setQty(): OrderProduct
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
        $moloniProduct = [];

        $wcProductId = $this->orderProduct->get_product_id();
        $wcVariationId = $this->orderProduct->get_variation_id();

        if ($wcVariationId > 0) {
            $association = ProductAssociations::findByWcId($wcVariationId);

            /** Association found, let's fetch by ID */
            if (!empty($association)) {
                $moloniProduct = $this->getById($association['ml_product_id']);
            }

            if (empty($moloniProduct)) {
                $moloniProduct = $this->getByProductParent();
            }

            /** Let's search by reference */
            if (empty($moloniProduct)) {
                $wcProduct = wc_get_product($wcVariationId);

                if (empty($wcProduct)) {
                    throw new DocumentError(__('Order products were deleted.','moloni_es'));
                }

                $moloniProduct = $this->getByReference($wcProduct);

                /** Not found, lets create a new product */
                if (empty($moloniProduct)) {
                    $wcProduct = wc_get_product($wcProductId);

                    if (empty($wcProduct)) {
                        throw new DocumentError(__('Order products were deleted.','moloni_es'));
                    }

                    SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProductId);

                    $service = new CreateVariantProduct($wcProduct);
                    $service->run();
                    $service->saveLog();

                    $moloniProduct = $service->getVariant($wcVariationId);
                }
            }
        } else {
            $association = ProductAssociations::findByWcId($wcProductId);

            /** Association found, let's fetch by ID */
            if (!empty($association)) {
                $moloniProduct = $this->getById($association['ml_product_id']);
            }

            /** Let's search by reference */
            if (empty($moloniProduct)) {
                $wcProduct = wc_get_product($wcProductId);

                if (empty($wcProduct)) {
                    throw new DocumentError(__('Order products were deleted.','moloni_es'));
                }

                $moloniProduct = $this->getByReference($wcProduct);

                /** Not found, lets create a new product */
                if (empty($moloniProduct)) {
                    SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProductId);

                    $service = new CreateSimpleProduct($wcProduct);
                    $service->run();
                    $service->saveLog();

                    $moloniProduct = $service->getMoloniProduct();
                }
            }
        }

        $this->product_id = $moloniProduct['productId'] ?? 0;

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
        $tax['taxId'] = (int) $moloniTax['taxId'];
        $tax['value'] = (float) $taxRate;
        $tax['ordering'] = count($this->taxes) + 1;
        $tax['cumulative'] = false;

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

    /**
     * Get product by ID
     *
     * @throws DocumentError
     */
    protected function getById(int $productId): array
    {
        $variables = [
            'productId' => $productId
        ];

        try {
            $byId = Products::queryProduct($variables);
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error fetching products', 'moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData(),
                ]
            );
        }

        return $byId['data']['product']['data'] ?? [];
    }

    /**
     * Get product by reference
     *
     * @throws DocumentError
     */
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

        try {
            $byReference = Products::queryProducts($variables);
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error fetching products', 'moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData(),
                ]
            );
        }

        if (!empty($byReference) && isset($byReference[0]['productId'])) {
            return $byReference[0];
        }

        return [];
    }

    /**
     * Get product parent
     *
     * @throws DocumentError
     */
    protected function getByProductParent(): array
    {
        $wcParentId = $this->orderProduct->get_product_id();
        $wcVariationId = $this->orderProduct->get_variation_id();

        $wcProduct = wc_get_product($wcParentId);

        if (empty($wcProduct)) {
            throw new DocumentError(__('Order products were deleted.','moloni_es'));
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
        SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $mlProduct['productId']);

        $service = new UpdateVariantProduct($wcProduct, $mlProduct);
        $service->run();
        $service->saveLog();

        $variant = $service->getVariant($wcVariationId);

        if (empty($variant)) {
            throw new DocumentError(__('Could not find variant after update.','moloni_es'));
        }

        return $variant;
    }
}
