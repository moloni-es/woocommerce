<?php

namespace MoloniES\Hooks;

use Exception;
use MoloniES\Controllers\Product;
use MoloniES\Error;
use MoloniES\Log;
use MoloniES\LogSync;
use MoloniES\Notice;
use MoloniES\Plugin;
use MoloniES\Start;

class ProductUpdate
{

    public $parent;

    /**
     * @param plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('woocommerce_update_product', [$this, 'productcreateupdate']);
    }

    public function productcreateupdate($productid)
    {
        try {
            $product = wc_get_product($productid);

            try {
                if ($product->get_status() !== 'draft' && start::login(true)) {
                    /** @noinspection NestedPositiveIfStatementsInspection */
                    if (defined('MOLONI_PRODUCT_SYNC') && MOLONI_PRODUCT_SYNC) {
                        $productObj = new product($product);

                        if (LogSync::wasSyncedRecently(1,$productid) === true) {
                            Log::write('Product has already been synced (WooCommerce -> Moloni)');
                            return;
                        }

                        if (!$productObj->loadbyreference()) {
                            $productObj->create();
                            if ($productObj->product_id > 0) {
                                Log::write(__('Product created on Moloni' , 'moloni_es'));
                                notice::addmessagesuccess(__('Product created on Moloni' , 'moloni_es'));
                            }
                        } else {
                            $productObj->update();
                            Log::write(__('Product updated on Moloni' , 'moloni_es'));
                            notice::addmessageinfo(__('Product updated on Moloni' , 'moloni_es'));
                        }
                    }
                }
            } catch (error $error) {
                notice::addmessagecustom(htmlentities($error->geterror()));
            }
        } catch (exception $ex) {
            log::write(__('Fatal error: ','moloni_es') . $ex->getmessage());
        }
    }
}
