<?php

namespace MoloniES\Services\MoloniProduct\Helpers\Variants;

class FindVariantByProperties
{
    private $wooCommerceAttributes;
    private $moloniProduct;

    public function __construct(array $wooCommerceAttributes, array $moloniProduct)
    {
        $this->wooCommerceAttributes = $wooCommerceAttributes;
        $this->moloniProduct = $moloniProduct;
    }

    public function handle()
    {
        foreach ($this->moloniProduct['variants'] as $moloniVariant) {
            if (count($this->wooCommerceAttributes) !== count($moloniVariant['propertyPairs'])) {
                continue;
            }

            foreach ($this->wooCommerceAttributes as $wcPropertyName => $wcPropertyValue) {
                $found = false;

                foreach ($moloniVariant['propertyPairs'] as $variantPropertyPairs) {
                    $moloniPropertyName = $variantPropertyPairs['property']['name'] ?? '';
                    $moloniPropertyValue = $variantPropertyPairs['propertyValue']['value'] ?? '';

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
            return $moloniVariant;
        }

        return [];
    }
}
