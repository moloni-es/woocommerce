<?php

namespace MoloniES\Services\MoloniProduct\Abstracts;

use WC_Tax;
use WC_Product;
use MoloniES\Tools;
use MoloniES\API\Companies;
use MoloniES\API\Products;
use MoloniES\API\Warehouses;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\ProductType;
use MoloniES\Controllers\ProductCategory;
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

    protected function setCategory()
    {
        $categoryId = 0;
        $categories = $this->wcProduct->get_category_ids();

        // Get the deepest category from all the trees
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
                    $categoryObj = new ProductCategory($category->name, $categoryId);

                    if (!$categoryObj->loadByName()) {
                        $categoryObj->create();
                    }

                    $categoryId = (int)$categoryObj->category_id;
                }
            }
        }

        if ($categoryId === 0) {
            $categoryName = __('Online Store','moloni_es');
            $categoryObj = new ProductCategory($categoryName, 0);

            if (!$categoryObj->loadByName()) {
                $categoryObj->create();
            }

            $categoryId = (int)$categoryObj->category_id;
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

    protected function setStock()
    {
        $hasStock = $this->wcProduct->managing_stock();

        $this->props['hasStock'] = $hasStock;

        if ($hasStock) {
            $warehouseId = defined('MOLONI_STOCK_SYNC_WAREHOUSE') ? (int)MOLONI_STOCK_SYNC_WAREHOUSE : 1;

            if ($warehouseId === 1) {
                $results = Warehouses::queryWarehouses();

                /** fail safe */
                $warehouseId = (int)$results[0]['warehouseId'];

                foreach ($results as $result) {
                    if ((bool)$result['isDefault'] === true) {
                        $warehouseId = (int)$result['warehouseId'];

                        break;
                    }
                }
            }

            $this->props['warehouseId'] = $warehouseId;
            $this->props['warehouses'] = [[
                'warehouseId' => $warehouseId,
                'stock' => (float)$this->wcProduct->get_stock_quantity()
            ]];
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
            $companyCountryCode = Companies::queryCompany();
            $companyCountryCode = $companyCountryCode['data']['company']['data']['fiscalZone']['fiscalZone'];

            foreach ($taxRates as $order => $taxRate) {
                $moloniTax = Tools::getTaxFromRate((float)$taxRate['rate'], $companyCountryCode);

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

    protected function setPropertyGroup()
    {
        if (empty($this->moloniProduct)) {
            $propertyGroup = (new FindOrCreatePropertyGroup($this->wcProduct))->handle();
        } else {
            $targetId = $this->moloniProduct['propertyGroup']['propertyGroupId'] ?? '';

            /**
             * Product already exists, so it has property group assigned
             * So we need to get the property group and update it if needed
             */
            $propertyGroup = (new GetOrUpdatePropertyGroup($this->wcProduct, $targetId))->handle();
        }

        $this->propertyGroup = $propertyGroup;

        $this->props['propertyGroupId'] = $propertyGroup['propertyGroupId'];
    }

    protected function setVariants()
    {
        $newVariants = [];

        foreach ($this->propertyGroup['variations'] as $wcVariationId => $targetPropertyGroup) {
            $wcVariation = wc_get_product($wcVariationId);

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

    protected function insert()
    {
        $data = [
            'data' => $this->props
        ];

        $mutation = Products::mutationProductCreate($data);

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

    protected function update()
    {
        $data = [
            'data' => $this->props
        ];

        $mutation = Products::mutationProductUpdate($data);

        $product = $mutation['data']['productUpdate']['data'] ?? [];

        if (empty($product)) {
            throw new ServiceException(
                sprintf(
                    __('Error %s product in Moloni (%s)', 'moloni_es'),
                    __('updating', 'moloni_es'),
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

    protected function uploadImage()
    {
        $wcImageId = $this->wcProduct->get_image_id();
        $oldMoloniImage = $this->moloniProduct['img'] ?? '';

        if ($wcImageId > 0 && !empty($oldMoloniImage)) {
            $wcImageName = get_the_title($wcImageId);

            /** If images have the same name there is no need to update image */
            if (str_contains($oldMoloniImage, $wcImageName)) {
                return;
            }
        }

        $url = wp_get_attachment_url($wcImageId);

        if ($url) {
            $uploads = wp_upload_dir();

            $image = str_replace($uploads['baseurl'], $uploads['basedir'], $url);

            $variables = [
                'data' => [
                    'productId' => (int)$this->moloniProduct['productId'],
                    'img' => null
                ],
            ];

            Products::mutationProductImageUpdate($variables, $image);
        }
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