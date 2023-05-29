<?php

namespace MoloniES\Services\MoloniProduct\Helpers;

use WC_Product;
use WC_Product_Variation;
use MoloniES\Traits\SyncFieldsSettingsTrait;

class ProductVariant
{
    use SyncFieldsSettingsTrait;

    private $props = [];

    /**
     * @var WC_Product|WC_Product_Variation
     */
    private $wcVariation;

    private $moloniParentVariants;

    private $propertyPairs;

    public function __construct($wcVariation, array $moloniParentVariants, array $propertyPairs)
    {
        $this->wcVariation = $wcVariation;
        $this->moloniParentVariants = $moloniParentVariants;
        $this->propertyPairs = $propertyPairs;

        $this->run();
    }

    //            Publics            //

    public function toArray(): array
    {
        return $this->props ?? [];
    }

    //            Privates            //

    private function run()
    {
        
    }
}