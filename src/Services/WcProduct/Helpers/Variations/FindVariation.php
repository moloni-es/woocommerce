<?php

namespace MoloniES\Services\WcProduct\Helpers\Variations;

use MoloniES\Tools\ProductAssociations;
use WC_Product;

class FindVariation
{
    /**
     * @var array
     */
    private $moloniVariant;

    /**
     * @var array
     */
    private $wcParentAttributes;

    /**
     * @var WC_Product[]
     */
    private $wcVariationsCache = [];

    public function __construct(array $wcParentAttributes, array $moloniVariant)
    {
        $this->moloniVariant = $moloniVariant;
        $this->wcParentAttributes = $wcParentAttributes;
    }

    public function run(): ?WC_Product
    {
        $targetVariation = $this->findVariantById();

        if (!empty($targetVariation)) {
            return $targetVariation;
        }

        /** Load all variations here */
        $this->loadVariationsCache();

        $targetVariation = $this->findVariantByAttributes();

        if (!empty($targetVariation)) {
            return $targetVariation;
        }

        return $this->findVariantByReference();
    }

    private function loadVariationsCache()
    {
        foreach ($this->wcParentAttributes as $variationId => $attributes) {
            if (count($attributes) !== count($this->moloniVariant['propertyPairs'])) {
                continue;
            }

            $tempVariant = wc_get_product($variationId);

            if (empty($tempVariant)) {
                continue;
            }

            $this->wcVariationsCache[] = $tempVariant;
        }
    }

    private function findVariantById(): ?WC_Product
    {
        /** Fetch by our associaitons table */
        $association = ProductAssociations::findByMoloniId($this->moloniVariant['productId']);

        if (!empty($association)) {
            $allChildIds = array_keys($this->wcParentAttributes);

            if (in_array($association['wc_product_id'], $allChildIds, false)) {
                $wcProduct = wc_get_product($association['wc_product_id']);

                if (!empty($wcProduct)) {
                    return $wcProduct;
                }

                ProductAssociations::deleteById($association['id']);
            }
        }

        return null;
    }

    private function findVariantByAttributes(): ?WC_Product
    {
        /** Fetch by attributes */
        foreach ($this->wcVariationsCache as $wcVariation) {
            $currentVariationAttributes = $this->wcParentAttributes[$wcVariation->get_id()];

            foreach ($this->moloniVariant['propertyPairs'] as $variantPropertyPairs) {
                $found = false;

                $moloniPropertyName = $variantPropertyPairs['property']['name'] ?? '';
                $moloniPropertyValue = $variantPropertyPairs['propertyValue']['value'] ?? '';

                foreach ($currentVariationAttributes as $wcPropertyName => $wcPropertyValue) {
                    if ($moloniPropertyName === $wcPropertyName && $moloniPropertyValue === $wcPropertyValue) {
                        $found = true;

                        break;
                    }
                }

                if (!$found) {
                    continue 2;
                }
            }

            /** A match was found, safely return */
            return $wcVariation;
        }

        return null;
    }

    private function findVariantByReference(): ?WC_Product
    {
        /** Fetch by reference */
        foreach ($this->wcVariationsCache as $wcVariation) {
            if ($wcVariation->get_sku() === $this->moloniVariant['reference']) {
                return $wcVariation;
            }
        }

        return null;
    }
}
