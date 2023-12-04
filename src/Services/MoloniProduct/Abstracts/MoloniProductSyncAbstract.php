<?php

namespace MoloniES\Services\MoloniProduct\Abstracts;

use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\HelperException;
use MoloniES\Helpers\MoloniWarehouse;
use MoloniES\Services\MoloniProduct\Helpers\GetOrCreateCategory;
use MoloniES\Services\MoloniProduct\Helpers\UpdateProductImages;
use WC_Tax;
use WC_Product;
use WC_Product_Variation;
use MoloniES\Tools;
use MoloniES\API\Companies;
use MoloniES\API\Products;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\ProductType;
use MoloniES\Exceptions\ServiceException;
use MoloniES\Enums\ProductIdentificationType;
use MoloniES\Services\MoloniProduct\Variant\MoloniVariant;
use MoloniES\Services\MoloniProduct\Helpers\Variants\FindOrCreatePropertyGroup;
use MoloniES\Services\MoloniProduct\Helpers\Variants\GetOrUpdatePropertyGroup;
use MoloniES\Services\MoloniProduct\Interfaces\MoloniProductServiceInterface;
use MoloniES\Traits\SyncFieldsSettingsTrait;

abstract class MoloniProductSyncAbstract implements MoloniProductServiceInterface
{
    use SyncFieldsSettingsTrait;

    /**
     * WooCommerce product
     *
     * @var WC_Product|null
     */
    protected $wcProduct;

    /**
     * Moloni Product
     *
     * @var array
     */
    protected $moloniProduct = [];

    /**
     * Create props
     *
     * @var array
     */
    protected $props = [];


    /**
     * WooCommerce's variation products
     *
     * @var WC_Product_Variation[]
     */
    protected $variationProductsCache = [];

    /**
     * Property group
     *
     * @var array
     */
    protected $propertyGroup = [];

    /**
     * Moloni variant services
     *
     * @var MoloniVariant[]
     */
    protected $variantServices = [];

    //            Sets            //

    protected function loadVariationProducts()
    {
        /** Let's load everything at the beginning, to do fewer queries to database */

        $variationIds = $this->wcProduct->get_children();

        if (empty($variationIds)) {
            return;
        }

        foreach ($variationIds as $variationId) {
            $this->variationProductsCache[$variationId] = wc_get_product($variationId);
        }
    }

    protected function setProductId()
    {
        $this->props['productId'] = $this->moloniProduct['productId'] ?? 0;
    }

    protected function setName()
    {
        $this->props['name'] = $this->wcProduct->get_name();
    }

    protected function setReference()
    {
        $reference = $this->wcProduct->get_sku();

        if (empty($reference)) {
            $reference = $this->createReferenceFromString($this->wcProduct->get_name());
        }

        $this->props['reference'] = $reference;
    }

    /**
     * Set category
     *
     * @throws ServiceException
     */
    protected function setCategory()
    {
        $categoryId = 0;
        $categories = $this->wcProduct->get_category_ids();

        /** Get the deepest category from all the trees */
        if (!empty($categories) && is_array($categories)) {
            $categoryTree = [];

            foreach ($categories as $category) {
                $parents = get_ancestors($category, 'product_cat');
                $parents = array_reverse($parents);
                $parents[] = $category;

                if (is_array($parents) && count($parents) > count($categoryTree)) {
                    $categoryTree = $parents;
                }
            }

            foreach ($categoryTree as $category) {
                $category = get_term_by('id', $category, 'product_cat');

                if (!empty($category->name)) {
                    try {
                        $categoryId = (new GetOrCreateCategory($category->name, $categoryId))->get();
                    } catch (HelperException $e) {
                        throw new ServiceException($e->getMessage(), $e->getData());
                    }
                }
            }
        }

        if ($categoryId === 0) {
            try {
                $categoryId = (new GetOrCreateCategory(__('Online Store', 'moloni_es')))->get();
            } catch (HelperException $e) {
                throw new ServiceException($e->getMessage(), $e->getData());
            }
        }

        $this->props['productCategoryId'] = $categoryId;
    }

    protected function setEan()
    {
        $identifications = [];
        $isEanFav = false;

        if (isset($this->moloniProduct['identifications']) && !empty($this->moloniProduct['identifications'])) {
            foreach ($this->moloniProduct['identifications'] as $identification) {
                if ($identification['type'] === ProductIdentificationType::EAN13) {
                    $isEanFav = $identification['favorite'];
                } else {
                    $identifications[] = $identification;
                }
            }
        }

        $metaBarcode = $this->wcProduct->get_meta('barcode', true);

        if (!empty($metaBarcode)) {
            $identifications[] = [
                'type' => 'EAN13',
                'text' => $metaBarcode,
                'favorite' => $isEanFav
            ];
        }

        $this->props['identifications'] = $identifications;
    }

    protected function setType()
    {
        if ($this->wcProduct->is_virtual() || $this->wcProduct->is_downloadable()) {
            $this->props['type'] = ProductType::SERVICE;
        } else {
            $this->props['type'] = ProductType::PRODUCT;
        }
    }

    /**
     * Set stock
     *
     * @throws ServiceException
     */
    protected function setStock()
    {
        $wcProductIsVariable = $this->wcProduct->is_type('variable');

        if ($wcProductIsVariable) {
            $hasStock = false;

            foreach ($this->variationProductsCache as $variationProduct) {
                if ($variationProduct->managing_stock()) {
                    $hasStock = true;

                    break;
                }
            }
        } else {
            $hasStock = $this->wcProduct->managing_stock();
        }

        $this->props['hasStock'] = $hasStock;

        if ($hasStock) {
            $warehouseId = defined('MOLONI_STOCK_SYNC_WAREHOUSE') ? (int)MOLONI_STOCK_SYNC_WAREHOUSE : 0;

            if (empty($warehouseId)) {
                try {
                    $warehouseId = MoloniWarehouse::getDefaultWarehouse();
                } catch (HelperException $e) {
                    throw new ServiceException($e->getMessage(), $e->getData());
                }
            }

            $this->props['warehouseId'] = $warehouseId;

            if ($wcProductIsVariable) {
                $this->props['warehouses'] = [
                    'warehouseId' => $warehouseId,
                ];
            } else {
                $this->props['warehouses'] = [[
                    'warehouseId' => $warehouseId,
                    'stock' => (float)$this->wcProduct->get_stock_quantity()
                ]];
            }
        }
    }

    protected function setPrice()
    {
        $this->props['price'] = (float)wc_get_price_excluding_tax($this->wcProduct);
    }

    protected function setSummary()
    {
        $this->props['summary'] = $this->wcProduct->get_short_description() ?? '';
    }

    protected function setNotes()
    {
        $this->props['notes'] = $this->wcProduct->get_description() ?? '';
    }

    protected function setMeasureUnit()
    {
        $this->props['measurementUnitId'] = defined('MEASURE_UNIT') ? (int)MEASURE_UNIT : 0;
    }

    protected function setTaxes()
    {
        $hasIVA = false;

        $this->props['taxes'] = [];
        $this->props['exemptionReason'] = '';

        if ($this->wcProduct->get_tax_status() === 'taxable') {
            // Get taxes based on a tax class of a product
            // If the tax class is empty it means the products uses the shop default
            $productTaxes = $this->wcProduct->get_tax_class();
            $taxRates = WC_Tax::get_base_tax_rates($productTaxes);

            // Get company setting to associate country code
            $query = Companies::queryCompany();

            $fiscalZone = [
                'code' => $query['data']['company']['data']['fiscalZone']['fiscalZone'],
                'countryId' => $query['data']['company']['data']['country']['countryId']
            ];

            foreach ($taxRates as $order => $taxRate) {
                $moloniTax = Tools::getTaxFromRate((float)$taxRate['rate'], $fiscalZone);

                $tax = [];
                $tax['taxId'] = (int)$moloniTax['taxId'];
                $tax['value'] = (float)$moloniTax['value'];
                $tax['ordering'] = (int)$order;
                $tax['cumulative'] = false;

                if ($moloniTax['value'] > 0) {
                    $this->props['taxes'][] = $tax;
                }

                if ((int)$moloniTax['type'] === 1) {
                    $hasIVA = true;
                }
            }
        }

        if (!$hasIVA) {
            $this->props['exemptionReason'] = defined('EXEMPTION_REASON') ? EXEMPTION_REASON : '';
        }
    }

    /**
     * Set property groups
     *
     * @throws ServiceException
     */
    protected function setPropertyGroup()
    {
        if (empty($this->moloniProduct)) {
            $targetId = '';
        } else {
            $targetId = $this->moloniProduct['propertyGroup']['propertyGroupId'] ?? '';
        }

        try {
            if (empty($targetId)) {
                $propertyGroup = (new FindOrCreatePropertyGroup($this->wcProduct))->handle();
            } else {
                $propertyGroup = (new GetOrUpdatePropertyGroup($this->wcProduct, $targetId))->handle();
            }
        } catch (HelperException $e) {
            throw new ServiceException($e->getMessage(), $e->getData());
        }

        $this->propertyGroup = $propertyGroup;

        $this->props['propertyGroupId'] = $propertyGroup['propertyGroupId'];
    }

    /**
     * Set variants
     *
     * @throws ServiceException
     */
    protected function setVariants()
    {
        $newVariants = [];

        foreach ($this->propertyGroup['variations'] as $wcVariationId => $targetPropertyGroup) {
            /** Get variation from cached objects array */
            $wcVariation = $this->variationProductsCache[$wcVariationId];

            if (empty($wcVariation)) {
                continue;
            }

            $service = new MoloniVariant(
                $wcVariation,
                $this->moloniProduct ?? [],
                $this->propertyGroup['variations'][$wcVariationId] ?? []
            );
            $service->findVariant();
            $service->run();

            $newVariants[] = $service->getProps();

            $this->variantServices[] = $service;
        }

        if (!empty($this->moloniProduct['variants'])) {
            foreach ($this->moloniProduct['variants'] as $existingVariant) {
                foreach ($newVariants as $newVariant) {
                    if (!isset($newVariant['productId'])) {
                        continue;
                    }

                    if ($existingVariant['productId'] === $newVariant['productId']) {
                        continue 2;
                    }
                }

                /** If we cannot delete variant, set it as invisible */
                if ($existingVariant['deletable'] === false) {
                    $newVariants[] = [
                        'productId' => $existingVariant['productId'],
                        'visible' => Boolean::NO,
                    ];
                }
            }
        }

        $this->props['variants'] = $newVariants;
    }

    //            Requests            //

    /**
     * @throws ServiceException
     */
    protected function insert()
    {
        $data = [
            'data' => $this->props
        ];

        try {
            $mutation = Products::mutationProductCreate($data);
        } catch (APIExeption $e) {
            throw new ServiceException(
                sprintf(
                    __('Error %s product in Moloni (%s)', 'moloni_es'),
                    __('creating', 'moloni_es'),
                    $this->props['reference'] ?? '---'
                ),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        }

        $product = $mutation['data']['productCreate']['data'] ?? [];

        if (empty($product)) {
            throw new ServiceException(
                sprintf(
                    __('Error %s product in Moloni (%s)', 'moloni_es'),
                    __('creating', 'moloni_es'),
                    $this->props['reference'] ?? '---'
                ),
                [
                    'mutation' => $mutation,
                    'data' => $data,
                ]
            );
        }

        $this->moloniProduct = $product;

        $this->afterSave();
    }

    /**
     * Update a Moloni product
     *
     * @throws ServiceException
     */
    protected function update()
    {
        $data = [
            'data' => $this->props
        ];

        try {
            $mutation = Products::mutationProductUpdate($data);
        } catch (APIExeption $e) {
            throw new ServiceException(
                sprintf(
                    __('Error %s product in Moloni (%s)', 'moloni_es'),
                    __('updating', 'moloni_es'),
                    $this->wcProduct->get_sku() ?? '---'
                ),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        }

        $product = $mutation['data']['productUpdate']['data'] ?? [];

        if (empty($product)) {
            throw new ServiceException(
                sprintf(
                    __('Error %s product in Moloni (%s)', 'moloni_es'),
                    __('updating', 'moloni_es'),
                    $this->wcProduct->get_sku() ?? '---'
                ),
                [
                    'mutation' => $mutation,
                    'data' => $data,
                ]
            );
        }

        $this->moloniProduct = $product;

        $this->afterSave();
    }

    protected function uploadImage()
    {
        $files = [];
        $wcImageId = $this->wcProduct->get_image_id();

        $url = wp_get_attachment_url($wcImageId);

        if ($url) {
            $uploads = wp_upload_dir();

            $files[0] = str_replace($uploads['baseurl'], $uploads['basedir'], $url);
        } else {
            $files[0] = '';
        }

        if (!empty($this->variantServices)) {
            foreach ($this->variantServices as $variantService) {
                $image = $variantService->getImage();
                $productId = $variantService->getMoloniVariantProductId();

                $files[$productId] = $image;
            }
        }

        new UpdateProductImages($files, $this->moloniProduct);
    }

    //            Gets            //

    public function getWcProduct(): ?WC_Product
    {
        return $this->wcProduct;
    }

    public function getMoloniProduct(): array
    {
        return $this->moloniProduct;
    }

    //            Auxiliary            //

    protected function afterSave()
    {
        if (!empty($this->variantServices)) {
            foreach ($this->variantServices as $variantService) {
                $variantService->setMoloniParentProduct($this->moloniProduct);
                $variantService->findVariant();
            }
        }
    }

    /**
     * Creates reference for product if missing
     *
     * @param string $string
     *
     * @return string
     */
    protected function createReferenceFromString(string $string): string
    {
        $reference = '';
        $name = explode(' ', $string);

        foreach ($name as $word) {
            $reference .= '_' . mb_substr($word, 0, 3);
        }

        return $reference;
    }

    //            Abstracts            //

    protected abstract function createAssociation();
}
