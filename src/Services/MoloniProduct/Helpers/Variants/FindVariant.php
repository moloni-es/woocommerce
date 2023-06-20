<?php
/**
 * 2022 - Moloni.com
 *
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 * DISCLAIMER
 *
 * @author    Moloni
 * @copyright Moloni
 * @license   https://creativecommons.org/licenses/by-nd/4.0/
 *
 * @noinspection PhpMultipleClassDeclarationsInspection
 */

namespace MoloniES\Services\MoloniProduct\Helpers\Variants;

use MoloniES\Tools\ProductAssociations;

class FindVariant
{
    private $wcProductId;
    private $wcProductReference;
    private $wantedPropertyPairs;
    private $allMoloniParentVariants;

    public function __construct(int $wcProductId, string $wcProductReference, array $allMoloniParentVariants, array $wantedPropertyPairs)
    {
        $this->wcProductId = $wcProductId;
        $this->wcProductReference = $wcProductReference;
        $this->wantedPropertyPairs = $wantedPropertyPairs;
        $this->allMoloniParentVariants = $allMoloniParentVariants;
    }

    public function run(): array
    {
        if (empty($this->allMoloniParentVariants)) {
            return [];
        }

        $association = ProductAssociations::findByWcId($this->wcProductId);

        if (!empty($association)) {
            $targetVariant = $this->findVariantById($association['ml_product_id']);

            if (!empty($targetVariant)) {
                return $targetVariant;
            }

            ProductAssociations::deleteById($association['id']);
        }

        $targetVariant = $this->findVariantByVariationReference();

        if (!empty($targetVariant)) {
            return $targetVariant;
        }

        return $this->findVariantByPropertyPairs();
    }

    private function findVariantById(int $needle): array
    {
        $variant = [];

        foreach ($this->allMoloniParentVariants as $parentVariant) {
            if ((int)$parentVariant['productId'] === $needle) {
                $variant = $parentVariant;

                break;
            }
        }

        return $variant;
    }

    private function findVariantByVariationReference(): array
    {
        $variant = [];

        if (empty($this->wcProductReference)) {
            return $variant;
        }

        foreach ($this->allMoloniParentVariants as $parentVariant) {
            if ($parentVariant['reference'] === $this->wcProductReference) {
                $variant = $parentVariant;

                break;
            }
        }

        return $variant;
    }

    private function findVariantByPropertyPairs()
    {
        $variant = [];

        foreach ($this->allMoloniParentVariants as $parentVariant) {
            if (count($this->wantedPropertyPairs) !== count($parentVariant['propertyPairs'])) {
                continue;
            }

            foreach ($this->wantedPropertyPairs as $propertyPair) {
                $found = false;

                foreach ($parentVariant['propertyPairs'] as $parentVariantPropertyPairs) {
                    if ($propertyPair['propertyId'] === $parentVariantPropertyPairs['propertyId'] &&
                        $propertyPair['propertyValueId'] === $parentVariantPropertyPairs['propertyValueId']) {
                        $found = true;

                        break;
                    }
                }

                if (!$found) {
                    continue 2;
                }
            }

            /** A match was found, safely return */
            $variant = $parentVariant;

            break;
        }

        return $variant;
    }
}
