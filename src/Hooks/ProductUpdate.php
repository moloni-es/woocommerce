<?php

namespace MoloniES\Hooks;

use Exception;
use WC_Product;
use MoloniES\Controllers\Product;
use MoloniES\Enums\LogSyncType;
use MoloniES\Exceptions\Error;
use MoloniES\LogSync;
use MoloniES\Notice;
use MoloniES\Plugin;
use MoloniES\Start;
use MoloniES\Storage;

class ProductUpdate
{
    /**
     * Main class
     *
     * @var Plugin
     */
    public $parent;

    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;
        add_action('woocommerce_update_product', [$this, 'productcreateupdate']);
    }

    public function productcreateupdate($productId)
    {
        if (!$this->shouldRunHook($productId)) {
            return;
        }

        try {
            $product = wc_get_product($productId);

            try {
                if ($this->shouldProcessProduct($product) && $this->shouldSyncProduct()) {
                    $this->updateOrInsertProduct($product);
                }
            } catch (Error $error) {
                Notice::addmessagecustom(htmlentities($error->geterror()));
            }
        } catch (exception $ex) {
            Storage::$LOGGER->critical(__('Fatal error'), [
                'action' => 'automatic:product:save',
                'exception' => $ex->getMessage()
            ]);
        }
    }

    //          Privates          //

    /**
     * Update/insert action
     *
     * @param WC_Product $product
     *
     * @throws Error
     */
    private function updateOrInsertProduct(WC_Product $product): void
    {
        $productObj = new Product($product);

        if (!$productObj->loadbyreference()) {
            $productObj->create();

            if ($productObj->product_id > 0) {
                Notice::addMessageSuccess(__('Product created on Moloni', 'moloni_es'));
            }
        } else {
            $productObj->update();
            Notice::addMessageInfo(__('Product updated on Moloni', 'moloni_es'));
        }
    }

    //          Auxiliary          //

    /**
     * Check if hook should be run
     *
     * @param int $productId
     *
     * @return bool
     */
    private function shouldRunHook(int $productId): bool
    {
        return !LogSync::wasSyncedRecently(LogSyncType::WC_PRODUCT, $productId);
    }

    /**
     * Check if product should be processed
     *
     * @param WC_Product|null $product
     *
     * @return bool
     */
    private function shouldProcessProduct(?WC_Product $product): bool
    {
        if (empty($product) || $product->get_status() === 'draft' || empty($product->get_sku())) {
            return false;
        }

        return Start::login(true);
    }

    /**
     * Check if product should be created
     *
     * @return bool
     */
    private function shouldSyncProduct(): bool
    {
        return (defined('MOLONI_PRODUCT_SYNC') && MOLONI_PRODUCT_SYNC);
    }
}
