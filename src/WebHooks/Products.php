<?php

namespace MoloniES\WebHooks;

use MoloniES\API\Categories;
use MoloniES\Error;
use MoloniES\Log;
use MoloniES\Model;
use MoloniES\Start;
use MoloniES\API\Products as ApiProducts;
use WC_Data_Exception;
use WC_Product;
use WC_Product_Attribute;
use WC_Product_Variable;
use WC_Product_Variation;

class Products
{
    /**
     * Products constructor.
     */
    public function __construct()
    {
        //create a new route
        register_rest_route('moloni/v1', 'products/(?P<hash>[a-f0-9]{32}$)', [
            'methods' => 'POST',
            'callback' => [$this, 'products']
        ]);
    }

    /**
     * Handles data form WebHook
     * @param $requestData
     * @return void
     * @throws Error|WC_Data_Exception
     */
    public function products($requestData)
    {
        $parameters = $requestData->get_params();

        //model has to be 'Product', needs to be logged in and recieved hash has to match logged in company id hash
        if ($parameters['model'] !== 'Product' || !Start::login(true) || !Model::checkHash($parameters['hash'])) {
            return;
        }

        $variables = [
            'companyId' => (int)MOLONIES_COMPANY_ID,
            'productId' => (int)sanitize_text_field($parameters['productId'])
        ];

        $moloniProduct = ApiProducts::queryProduct($variables); //get product that was received from the hook

        if (empty($moloniProduct['data']['product']['data'])) {
            return; //it happens when searching for variant id (god knows why)
        }

        $moloniProduct = $moloniProduct['data']['product']['data'];

        //switch between operations
        switch ($parameters['operation']) {
            case 'create':
                $this->add($moloniProduct);
                break;
            case 'update':
                $this->update($moloniProduct);
                break;
            case 'stockChanged':
                //if the changed product was a variant (because stock changes appens at variant level)
                if (empty($moloniProduct['variants'])) {
                    $this->stockUpdate($moloniProduct);
                } else {
                    //for each variant check and update its stock
                    foreach ($moloniProduct['variants'] as $variant) {
                        $this->stockUpdate($variant);
                    }
                }
                break;
        }
    }

    /**
     * Adds new product
     * @param $moloniProduct
     * @throws WC_Data_Exception|Error
     */
    public function add($moloniProduct)
    {
        if (!defined('HOOK_PRODUCT_ADD') || (int)HOOK_PRODUCT_ADD === 0) {
            return;
        }

        $wcProductId = wc_get_product_id_by_sku($moloniProduct['reference']);

        //if the product does not exist
        if ($wcProductId === 0) {
            $wcProduct = $this->setProduct($moloniProduct, $wcProductId);

            //variants need to be added after the parent is added
            //create variants if the moloni array has them
            if (!empty($moloniProduct['variants'])) {
                $this->setVariants($moloniProduct, $wcProduct);
            }

            Log::write(sprintf(__('Product created in WooCommerce: %s', 'moloni_es'), $moloniProduct['reference']));
        } else {
            Log::write(sprintf(__('Product already exists in WooCommerce: %s', 'moloni_es'), $moloniProduct['reference']));
        }
    }

    /**
     * Updates product
     * @param $moloniProduct array
     * @throws WC_Data_Exception
     * @throws Error
     */
    public function update($moloniProduct)
    {
        if (!defined('HOOK_PRODUCT_UPDATE') || (int)HOOK_PRODUCT_UPDATE === 0) {
            return;
        }

        $wcProductId = wc_get_product_id_by_sku($moloniProduct['reference']);

        if ($wcProductId > 0) {
            $wcProduct = $this->setProduct($moloniProduct, $wcProductId);

            //variants need to be added after the parent is added
            //check if user wants to update products with variants
            if (defined('MOLONI_VARIANTS_SYNC') && (int)MOLONI_VARIANTS_SYNC === 1) {
                if (!empty($moloniProduct['variants'])) {
                    $this->setVariants($moloniProduct, $wcProduct);
                }
            }

            Log::write(sprintf(__('Product updated in WooCommerce: %s', 'moloni_es'), $moloniProduct['reference']));
        } else {
            Log::write(sprintf(__('Product not found in WooCommerce to update: %s', 'moloni_es'), $moloniProduct['reference']));
        }
    }

    /**
     * Updates a product stock
     * @param $moloniProduct
     */
    public function stockUpdate($moloniProduct)
    {
        if (!defined('HOOK_STOCK_UPDATE') || (int)HOOK_STOCK_UPDATE === 0) {
            return;
        }

        $wcProductId = wc_get_product_id_by_sku($moloniProduct['reference']);

        //if it exists and manage stock is set to true, update it
        if ($wcProductId > 0 && (get_post_meta($wcProductId, '_manage_stock'))[0] !== 'no') {
            $currentStock = get_post_meta($wcProductId, '_stock', true);
            $newStock = $moloniProduct['stock'];

            if ((float)$currentStock === (float)$newStock) {
                Log::write(sprintf(__('Product with reference %1$s already was up-to-date %2$s | %3$s', 'moloni_es'), $moloniProduct['reference'], $currentStock, $newStock));
            } else {
                update_post_meta($wcProductId, '_stock', $newStock);
                update_post_meta($wcProductId, '_stock_status', ($newStock > 0 ? 'instock' : 'onbackorder'));
                update_post_meta($wcProductId, 'outofstock', ($newStock > 0 ? '0' : '1'));

                Log::write(sprintf(__('Product with reference %1$s was updated from %2$s to %3$s', 'moloni_es'), $moloniProduct['reference'], $currentStock, $newStock));
            }
        } else {
            Log::write(sprintf(__('Product not found in WooCommerce or without active stock: %s', 'moloni_es'), $moloniProduct['reference']));
        }
    }

    /**
     * Creates/updates an product based on received moloni product
     * @param $wcId int WooCommerce product id
     * @param $moloniProduct array moloni product
     * @return WC_Product
     * @throws Error
     * @throws WC_Data_Exception
     */
    public function setProduct($moloniProduct, $wcId)
    {
        if ($wcId === 0) { //create a new product
            $wcProduct = new WC_Product();
        } else { //update an existing product
            $wcProduct = wc_get_product($wcId);
        }

        if (defined('SYNC_FIELDS_NAME') && (int)SYNC_FIELDS_NAME === 1) {
            $wcProduct->set_name($moloniProduct['name']);
        }

        $wcProduct->set_sku($moloniProduct['reference']); //required

        if (defined('SYNC_FIELDS_DESCRIPTION') && (int)SYNC_FIELDS_DESCRIPTION === 1) {
            $wcProduct->set_description($moloniProduct['summary']);
            $wcProduct->set_short_description($moloniProduct['summary']);
        }

        if (defined('SYNC_FIELDS_PRICE') && (int)SYNC_FIELDS_PRICE === 1) {
            if (!defined('TAX_ID') || (int)TAX_ID === 0) {
                $wcProduct->set_regular_price($moloniProduct['price']);
            } else {
                $wcProduct->set_regular_price($moloniProduct['priceWithTaxes']);
            }
        }

        if (defined('SYNC_FIELDS_VISIBILITY') && (int)SYNC_FIELDS_VISIBILITY === 1) {
            $wcProduct->set_catalog_visibility((int)$moloniProduct['visible'] === 1 ? 'visible' : 'hidden');
        }

        if (defined('SYNC_FIELDS_STOCK') && (int)SYNC_FIELDS_STOCK === 1) {
            $wcProduct->set_manage_stock((bool)$moloniProduct['hasStock']);

            if ((int)$moloniProduct['hasStock'] === 1) {
                $wcProduct->set_stock_quantity($moloniProduct['stock']);
                $wcProduct->set_low_stock_amount($moloniProduct['minStock']);
            }
        }

        if (defined('SYNC_FIELDS_CATEGORIES') && (int)SYNC_FIELDS_CATEGORIES === 1) {
            $wcProduct->set_category_ids($this->setCategories($moloniProduct['productCategory']['productCategoryId']));
        }

        $wcProduct->save();

        return $wcProduct;
    }

    /**
     * Sets product variants
     * @param $moloniProduct
     * @param WC_Product $wcProduct
     * @throws WC_Data_Exception
     */
    public function setVariants($moloniProduct, $wcProduct)
    {
        //if product has variants, the stock is managed in variants level
        $wcProduct->set_manage_stock(false);
        $wcProduct->save();

        //set this product attributes
        $this->setAttributes($moloniProduct, $wcProduct->get_id());

        foreach ($moloniProduct['variants'] as $variation) {
            $existsInWC = wc_get_product_id_by_sku($variation["reference"]);

            //check if we are going to update or create
            if ($existsInWC !== 0) {
                $objVariation = new WC_Product_Variation(wc_get_product(wc_get_product_id_by_sku($variation["reference"])));
            } else {
                $objVariation = new WC_Product_Variation();
            }

            $objVariation->set_parent_id($wcProduct->get_id());
            $objVariation->set_sku($variation["reference"]);

            if (defined('SYNC_FIELDS_PRICE') && (int)SYNC_FIELDS_PRICE === 1) {
                if (!defined('TAX_ID') || (int)TAX_ID === 0) {
                    $objVariation->set_regular_price($moloniProduct['price']);
                } else {
                    $objVariation->set_regular_price($moloniProduct['priceWithTaxes']);
                }
            }

            if (defined('SYNC_FIELDS_VISIBILITY') && (int)SYNC_FIELDS_VISIBILITY === 1) {
                $objVariation->set_catalog_visibility((int)$moloniProduct['visible'] === 1 ? 'visible' : 'hidden');
            }

            if (defined('SYNC_FIELDS_STOCK') && (int)SYNC_FIELDS_STOCK === 1) {
                $objVariation->set_manage_stock($variation["hasStock"]);
                $objVariation->set_stock_quantity($variation["stock"]);
            }

            $var_attributes = [];

            foreach ($variation["propertyPairs"] as $value) {
                $var_attributes[strtolower($value['property']["name"])] = $value["propertyValue"]['value'];
            }

            $objVariation->set_attributes($var_attributes);
            $objVariation->save();

            if ($existsInWC !== 0) {
                Log::write(sprintf(__('Variant updated in WooCommerce: %s', 'moloni_es'), $variation['reference']));

            } else {
                Log::write(sprintf(__('Variant Created in WooCommerce: %s', 'moloni_es'), $variation['reference']));
            }
        }
    }

    /**
     * Creates/updates products attributtes
     * @param $moloniProduct array
     * @param $wcProductId int
     * @return void
     */
    public function setAttributes($moloniProduct, $wcProductId)
    {
        $attributes = $this->getAttributes($moloniProduct);
        $productAttributes = [];

        $product = new WC_Product_Variable($wcProductId);

        foreach ($attributes as $name => $options) {
            $attribute = new WC_Product_Attribute();
            $attribute->set_name($name);
            $attribute->set_options($options);
            $attribute->set_visible(true);
            $attribute->set_variation(true);
            $productAttributes[] = $attribute;
        }

        $product->set_attributes($productAttributes);
        $product->save();
    }

    /**
     * Sets product categories
     * @param $moloniCategoryId
     * @return array
     * @throws Error
     */
    public function setCategories($moloniCategoryId)
    {

        $namesArray = $this->getCategoriesFromMoloni($moloniCategoryId); //all names from category tree

        $categoriesIds = [];
        $parentId = 0;

        foreach ($namesArray as $prod_cat) {
            if (!term_exists($prod_cat, 'product_cat', $parentId)) {
                $term = wp_insert_term($prod_cat, 'product_cat', ['parent' => $parentId]);
                $parentId = $term['term_id'];

                array_unshift($categoriesIds, $term['term_id']);
            } else {
                $term_s = get_term_by('name', $prod_cat, 'product_cat');
                $parentId = $term_s->term_id;

                array_unshift($categoriesIds, $term_s->term_id);
            }
        }

        return $categoriesIds;
    }

    /////////////////////////// AUXILIARY METHODS ///////////////////////////

    /**
     * Returns product variants attributes
     * [att1 => [prop1, prop3, prop3], att2 => [prop1]]
     * @param $moloniProduct
     * @return array
     */
    public function getAttributes($moloniProduct)
    {
        $attributes = [];

        foreach ($moloniProduct['variants'] as $variant) {
            foreach ($variant['propertyPairs'] as $property) {
                if (!in_array($property['propertyValue']['value'], $attributes[$property['property']['name']], true)) {
                    $attributes[$property['property']['name']][] = $property['propertyValue']['value'];
                }
            }
        }

        return $attributes;
    }

    /**
     * Gets categories tree from moloni
     * @param $moloniCategoryId
     * @return array|bool
     * @throws Error
     */
    public function getCategoriesFromMoloni($moloniCategoryId)
    {
        $moloniId = $moloniCategoryId;//current category id
        $moloniCategoriesTree = [];
        $failsafe = 0; //we dont want the while loop to be stuck

        if ($moloniCategoryId === null) {
            return $moloniCategoriesTree; //can happen because product can have no category in moloni.es
        }

        do {
            $variables = [
                'companyId' => (int)MOLONIES_COMPANY_ID,
                'productCategoryId' => (int)$moloniId
            ];

            $query = (Categories::queryProductCategory($variables))['data']['productCategory']['data'];

            array_unshift($moloniCategoriesTree, $query['name']); //order needs to be inverted

            if ($query['parent'] === null) {
                break 1; //break if category has no parent
            }

            $moloniId = $query['parent']['productCategoryId']; //next current id is this category parent

            $failsafe++;
        } while ($failsafe < 100);

        return $moloniCategoriesTree; //returns the names of all categories (from this product only)
    }
}