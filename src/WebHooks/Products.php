<?php

namespace MoloniES\WebHooks;

use Exception;
use MoloniES\API\Categories;
use MoloniES\Error;
use MoloniES\Log;
use MoloniES\LogSync;
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
            'callback' => [$this, 'products'],
            'permission_callback' => '__return_true'
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
        try {
            $parameters = $requestData->get_params();

            //model has to be 'Product', needs to be logged in and received hash has to match logged in company id hash
            if ($parameters['model'] !== 'Product' || !Start::login(true) || !Model::checkHash($parameters['hash'])) {
                return;
            }

            $variables = [
                'productId' => (int)sanitize_text_field($parameters['productId'])
            ];

            $moloniProduct = ApiProducts::queryProduct($variables); //get product that was received from the hook
            $moloniProduct = $moloniProduct['data']['product']['data'];

            if ($moloniProduct['parent'] !== null) {
                return; //we only want to update the main product
            }

            //switch between operations
            switch ($parameters['operation']) {
                case 'create':
                case 'update':
                    $this->save($moloniProduct);
                    break;
                case 'stockChanged':
                    //if the changed product was a variant (because stock changes happens at variant level)
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
        } catch (Exception $exception) {
            echo json_encode(['valid' => 0, 'error' => $exception->getMessage()]);
            exit;
        }
    }

    /**
     * Adds new product
     * @param $moloniProduct
     * @throws WC_Data_Exception|Error
     */
    public function save($moloniProduct)
    {
        if (!defined('HOOK_PRODUCT_SYNC') || (int)HOOK_PRODUCT_SYNC === 0) {
            return;
        }

        $wcProductId = wc_get_product_id_by_sku($moloniProduct['reference']);

        if (LogSync::wasSyncedRecently(1,$wcProductId) === true) {
            Log::write('Product has already been synced (Moloni -> WooCommerce)');
            return;
        }

        $wcProduct = $this->setProduct($moloniProduct, $wcProductId);

        $action = $wcProductId === 0 ? __('created', 'moloni_es') : __('updated', 'moloni_es');
        Log::write(sprintf(__('Product %s in WooCommerce: %s', 'moloni_es'), $action, $moloniProduct['reference']));

        //Variants need to be added after the parent is added
        //Check if user wants to update products with variants, or is a new product
        if ((defined('MOLONI_VARIANTS_SYNC') && (int)MOLONI_VARIANTS_SYNC === 1) || $wcProductId === 0) {
            if (!empty($moloniProduct['variants'])) {
                $this->setVariants($moloniProduct, $wcProduct);
            }
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

        if (LogSync::wasSyncedRecently(1,$wcProductId) === true) {
            Log::write('Product has already been synced (WooCommerce -> Moloni)');
            return;
        }

        $wcProduct = wc_get_product($wcProductId);

        //if it exists and manage stock is set to true, update it
        if ($wcProduct && $wcProduct->managing_stock()) {
            $currentStock = $wcProduct->get_stock_quantity();
            $newStock = $moloniProduct['stock'];

            if ((float)$currentStock === (float)$newStock) {
                Log::write(sprintf(__('Product with reference %1$s already was up-to-date %2$s | %3$s', 'moloni_es'), $moloniProduct['reference'], $currentStock, $newStock));
            } else {
                Log::write(sprintf(__('Product with reference %1$s was updated from %2$s to %3$s', 'moloni_es'), $moloniProduct['reference'], $currentStock, $newStock));
            }

            wc_update_product_stock($wcProductId, $newStock);
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
            $wcProduct->set_short_description($moloniProduct['summary']);
            $wcProduct->set_description($moloniProduct['notes']);
        }

        if (defined('SYNC_FIELDS_PRICE') && (int)SYNC_FIELDS_PRICE === 1) {
            if (wc_prices_include_tax()) {
                $wcProduct->set_regular_price($moloniProduct['priceWithTaxes']);
            } else {
                $wcProduct->set_regular_price($moloniProduct['price']);
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

        if (defined('SYNC_FIELDS_EAN') && (int)SYNC_FIELDS_EAN === 1) {
            foreach ($moloniProduct['identifications'] as $identification) {
                if ($identification['type'] === 'EAN13') {
                    if ($wcProduct->get_meta('_barcode', true) === false) {
                        $wcProduct->add_meta_data('_barcode', $identification['text']);
                    } else {
                        $wcProduct->update_meta_data('_barcode', $identification['text']);
                    }

                    break;
                }
            }
        }

        if (defined('SYNC_FIELDS_IMAGE') && (int)SYNC_FIELDS_IMAGE === 1 && !empty($moloniProduct['img'])) {
            $this->setImages($moloniProduct, $wcProduct);
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
        // Variants ids found
        $relevantIds = [];

        //if product has variants, the stock is managed in variants level
        $wcProduct->set_manage_stock(false);
        $wcProduct->save();

        //set this product attributes
        $this->setAttributes($moloniProduct, $wcProduct->get_id());

        foreach ($moloniProduct['variants'] as $variation) {
            $existsInWC = wc_get_product_id_by_sku($variation["reference"]);

            //check if we are going to update or create
            if ($existsInWC !== 0) {
                $objVariation = new WC_Product_Variation($existsInWC);
            } else {
                $objVariation = new WC_Product_Variation();
            }

            $objVariation->set_parent_id($wcProduct->get_id());
            $objVariation->set_sku($variation["reference"]);

            if (defined('SYNC_FIELDS_DESCRIPTION') && (int)SYNC_FIELDS_DESCRIPTION === 1) {
                $objVariation->set_description($variation['summary']);
                $objVariation->set_short_description($variation['summary']);
            }

            if (defined('SYNC_FIELDS_PRICE') && (int)SYNC_FIELDS_PRICE === 1) {
                if (wc_prices_include_tax()) {
                    $objVariation->set_regular_price($variation['priceWithTaxes']);
                } else {
                    $objVariation->set_regular_price($variation['price']);
                }
            }

            if (defined('SYNC_FIELDS_VISIBILITY') && (int)SYNC_FIELDS_VISIBILITY === 1) {
                $objVariation->set_catalog_visibility((int)$variation['visible'] === 1 ? 'visible' : 'hidden');
            }

            if (defined('SYNC_FIELDS_STOCK') && (int)SYNC_FIELDS_STOCK === 1) {
                $objVariation->set_manage_stock($variation["hasStock"]);
                $objVariation->set_stock_quantity($variation["stock"]);
            }

            if (defined('SYNC_FIELDS_IMAGE') && (int)SYNC_FIELDS_IMAGE === 1 && !empty($variation['img'])) {
                $this->setImages($variation, $objVariation);
            }


            $var_attributes = [];

            foreach ($variation["propertyPairs"] as $value) {
                $propertyName = self::cleanAttributeString($value['property']["name"]);
                $propertyValue = self::cleanAttributeString($value['propertyValue']["value"]);

                $var_attributes[sanitize_title($propertyName)] = $propertyValue;
            }

            $objVariation->set_attributes($var_attributes);
            $objVariation->save();

            if ($existsInWC !== 0) {
                Log::write(sprintf(__('Variant updated in WooCommerce: %s', 'moloni_es'), $variation['reference']));

            } else {
                Log::write(sprintf(__('Variant created in WooCommerce: %s', 'moloni_es'), $variation['reference']));
            }

            $relevantIds[] = $objVariation->get_id();
        }

        // Get all current product variations
        $allProductVariations = $wcProduct->get_children();

        // We need to delete all other variations
        foreach ($allProductVariations as $productVariation) {
            if (!in_array((int)$productVariation, $relevantIds, true)) {
                $tempWcProduct = new WC_Product_Variation($productVariation);
                $tempReference = $tempWcProduct->get_sku();

                $tempWcProduct->delete(true);

                Log::write(sprintf(__('Variant deleted in WooCommerce: %s', 'moloni_es'), $tempReference));
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
        $attributes = self::getAttributes($moloniProduct);
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

        $namesArray = self::getCategoriesFromMoloni($moloniCategoryId); //all names from category tree

        $categoriesIds = [];
        $parentId = 0;

        foreach ($namesArray as $prod_cat) {
            $existingTerm = term_exists($prod_cat, 'product_cat', $parentId);

            if (!$existingTerm) {
                $newTerm = wp_insert_term($prod_cat, 'product_cat', ['parent' => $parentId]);
                $parentId = $newTerm['term_id'];

                array_unshift($categoriesIds, $newTerm['term_id']);
            } else {
                $parentId = $existingTerm['term_id'];

                array_unshift($categoriesIds, $existingTerm['term_id']);
            }
        }

        return $categoriesIds;
    }

    /**
     * Sets product image
     *
     * @param $moloniProduct
     * @param WC_Product $wcProduct
     *
     * @return bool
     */
    public function setImages($moloniProduct, &$wcProduct)
    {
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $imageUrl = 'https://mediaapi.moloni.org' . $moloniProduct['img'];
        $uploadDir = wp_upload_dir();
        $imgRequest = wp_remote_get($imageUrl);

        if (is_wp_error($imgRequest)) {
            Log::write('Could not get image content.');
            return false;
        }

        $image_data = $imgRequest['body'];
        $filename = basename($imageUrl);

        if (wp_mkdir_p($uploadDir['path'])) {
            $file = $uploadDir['path'] . '/' . $filename;
        } else {
            $file = $uploadDir['basedir'] . '/' . $filename;
        }

        file_put_contents($file, $image_data);

        $wpFiletype = wp_check_filetype($filename, null);

        $attachment = [
            'post_mime_type' => $wpFiletype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $imageId = wp_insert_attachment($attachment, $file);
        $attachData = wp_generate_attachment_metadata($imageId, $file);

        wp_update_attachment_metadata($imageId, $attachData);

        $wcProduct->set_image_id($imageId);

        return true;
    }

    /////////////////////////// AUXILIARY METHODS ///////////////////////////

    /**
     * Returns product variants attributes
     * [att1 => [prop1, prop3, prop3], att2 => [prop1]]
     * @param $moloniProduct
     * @return array
     */
    public static function getAttributes($moloniProduct)
    {
        $attributes = [];

        foreach ($moloniProduct['variants'] as $variant) {
            foreach ($variant['propertyPairs'] as $property) {
                if (!in_array($property['propertyValue']['value'], $attributes[$property['property']['name']], true)) {
                    $propertyName = self::cleanAttributeString($property['property']['name']);
                    $propertyValue = self::cleanAttributeString($property['propertyValue']['value']);

                    $attributes[$propertyName][] = $propertyValue;
                }
            }
        }

        return $attributes;
    }

    /**
     * Cleans an string to be used as an attribute identifier
     *
     * @param string $string String to clean
     *
     * @return string Clean string
     */
    private static function cleanAttributeString($string = '')
    {
        return trim($string);
    }

    /**
     * Gets categories tree from moloni
     * @param $moloniCategoryId
     * @return array|bool
     * @throws Error
     */
    public static function getCategoriesFromMoloni($moloniCategoryId)
    {
        $moloniId = $moloniCategoryId;//current category id
        $moloniCategoriesTree = [];
        $failsafe = 0; //we dont want the while loop to be stuck

        if ($moloniCategoryId === null) {
            return $moloniCategoriesTree; //can happen because product can have no category in moloni.es
        }

        do {
            $variables = [
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
