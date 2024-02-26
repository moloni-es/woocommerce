<?php

namespace MoloniES\Services\MoloniProduct\Helpers\Variants;

use MoloniES\Exceptions\HelperException;
use WC_Product;
use WC_Product_Attribute;
use WP_Term;

class ParseProductProperties
{
    private $wcProduct;

    public function __construct(WC_Product $wcProduct)
    {
        $this->wcProduct = $wcProduct;
    }

    /**
     * Runner
     *
     * @throws HelperException
     */
    public function handle(): array
    {
        $tempParsedAttributes = [];

        /**
         * [
         *      'wc_product_id => [
         *          'attribute_name' => [
         *              'option_a',
         *              'option_b',
         *              ...
         *          ]
         *      ]
         * ]
         */
        $result = [];

        /** @var WC_Product_Attribute[] $attributes */
        $attributes = $this->wcProduct->get_attributes();

        foreach ($attributes as $attributeTaxonomy => $productAttribute) {
            $attributeObject = wc_get_attribute($productAttribute->get_id());

            if (empty($attributeObject)) {
                /**
                 * Hackermannnnnn
                 */

                $attributeName = $productAttribute->get_name();
            } else {
                $attributeName = $attributeObject->name;
            }

            if (!isset($tempParsedAttributes[$attributeTaxonomy])) {
                $tempParsedAttributes[$attributeTaxonomy] = [
                    'name' => $attributeName,
                    'options' => [],
                ];
            }

            if (empty($attributeObject)) {
                /**
                 * Hackermannnnnn
                 */

                $rawData = $productAttribute->get_data();

                if (empty($rawData['options'])) {
                    throw new HelperException(__('Product attribute not found', 'moloni_es'));
                }

                foreach ($rawData['options'] as $option) {
                    $littleHack = [
                        'slug' => $option,
                        'name' => $option,
                        'term_id' => 0,
                    ];

                    $tempParsedAttributes[$attributeTaxonomy]['options'][] = new WP_Term((object)$littleHack);
                }
            } else {
                $tempParsedAttributes[$attributeTaxonomy]['options'] = wc_get_product_terms($this->wcProduct->get_id(), $attributeTaxonomy);
            }
        }

        $ids = $this->wcProduct->get_children();

        foreach ($ids as $id) {
            $variationAttributes = wc_get_product($id)->get_attributes();

            $result[$id] = [];

            foreach ($variationAttributes as $taxonomy => $option) {
                if (empty($option)) {
                    continue;
                }

                $attributeName = $tempParsedAttributes[$taxonomy]['name'];

                $result[$id][$attributeName] = [];

                /** @var WP_Term $wpTerm */
                foreach ($tempParsedAttributes[$taxonomy]['options'] as $wpTerm) {
                    if ($wpTerm->slug === $option) {
                        $result[$id][$attributeName][] = $wpTerm->name;

                        break;
                    }
                }
            }
        }

        return $result;
    }
}
