<?php

namespace MoloniES\Hooks;

use MoloniES\Exceptions\Error;
use WC_Order;
use MoloniES\Plugin;
use MoloniES\Start;
use MoloniES\Storage;
use MoloniES\Helpers\MoloniOrder;

/**
 * Class OrderList
 * Add a Moloni column orders list
 *
 * @package Moloni\Hooks
 */
class OrderList
{
    /**
     * Caller class
     *
     * @var Plugin
     */
    public $parent;

    /**
     * If user want to show Moloni column
     * @var null|bool
     */
    private static $columnVisible;

    /**
     * OrderList constructor
     *
     * @param Plugin $parent Caller
     *
     * @return void
     */
    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        if (Storage::$USES_NEW_ORDERS_SYSTEM) {
            /**
             * HPOS usage is enabled.
             *
             * @see https://github.com/woocommerce/woocommerce/issues/35049
             * @see https://developer.woocommerce.com/2022/10/11/hpos-upgrade-faqs/
             */
            add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'ordersListAddColumn'], 10, 1);
            add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'ordersListManageColumn'], 10, 2);
        } else {
            /**
             * Traditional CPT-based orders are in use.
             */
            add_filter('manage_edit-shop_order_columns', [$this, 'ordersListAddColumn'], 10, 1);
            add_action('manage_shop_order_posts_custom_column', [$this, 'ordersListManageColumn'], 10, 2);
        }
    }

    /**
     * Appends Moloni column list
     *
     * @param array $oldColumns Columns list
     *
     * @return array
     */
    public function ordersListAddColumn(array $oldColumns): array
    {
        if (!$this->canShowColumn()) {
             return $oldColumns;
        }

        $newColumns = [];

        foreach ($oldColumns as $name => $info) {
            $newColumns[$name] = $info;

            if ('order_status' === $name) {
                $newColumns['moloni_document'] = __('Moloni document', 'moloni_es');
            }
        }

        return $newColumns;
    }

    /**
     * Draws Moloni column content
     *
     * @param string $currentColumnName Current column name
     * @param $orderOrPostId
     *
     * @return void
     */
    public function ordersListManageColumn(string $currentColumnName, $orderOrPostId)
    {
        if (!$this->canShowColumn()) {
            return;
        }

        if ($currentColumnName === 'moloni_document') {
            $order = new WC_Order($orderOrPostId);

            $documentId = MoloniOrder::getLastCreatedDocument($order);

            if ($documentId > 0) {
                $redirectUrl = admin_url('admin.php?page=molonies&action=downloadDocument&id=' . $documentId);

                echo '<a class="button" target="_blank" onclick="window.open(\'' . $redirectUrl . '\', \'_blank\')">' . __('Download', 'moloni_es') . '</a>';
            } else {
                echo '<div>' . __('No associated document', 'moloni_es') . '</div>';
            }
        }
    }

    /**
     * Verifies if user wants to show column
     *
     * @return bool
     */
    private function canShowColumn(): ?bool
    {
        if (self::$columnVisible === null) {
            try {
                self::$columnVisible = Start::login(true) && defined('MOLONI_SHOW_DOWNLOAD_COLUMN') && (int)MOLONI_SHOW_DOWNLOAD_COLUMN === 1;
            } catch (Error $e) {
                self::$columnVisible = false;
            }
        }

        return self::$columnVisible;
    }
}
