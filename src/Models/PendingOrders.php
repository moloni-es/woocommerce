<?php

namespace MoloniES\Models;

use MoloniES\Storage;
use WC_Order;
use WP_Query;

class PendingOrders
{
    private static $limit = 50;
    private static $ordersStatuses = ['wc-processing', 'wc-completed'];
    private static $totalPages = 1;
    private static $currentPage = 1;

    public static function getAllAvailable()
    {
        self::$currentPage = (int)sanitize_text_field(isset($_GET['paged']) ? $_GET['paged'] : 1);
        self::$currentPage = self::$currentPage > 0 ? self::$currentPage : 1;

        if (Storage::$USES_NEW_ORDERS_SYSTEM) {
            $ordersList = self::getAllNewSystem();
        } else {
            $ordersList = self::getAllOldSystem();
        }

        return $ordersList;
    }

    public static function getPagination()
    {
        $args = [
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'current' => isset($_GET['paged']) ? (int)$_GET['paged'] : 1,
            'total' => self::$totalPages,
        ];

        return paginate_links($args);
    }

    //           Privates           //

    /**
     * Fetch orders "old school" way
     *
     * @return array
     */
    private static function getAllOldSystem()
    {
        $ordersList = [];

        $args = [
            'post_type' => 'shop_order',
            'post_status' => self::$ordersStatuses,
            'posts_per_page' => self::$limit,
            'paged' => self::$currentPage,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_molonies_sent',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => '_molonies_sent',
                    'value' => '0',
                    'compare' => '='
                ]
            ],
        ];

        $args = apply_filters('moloni_before_pending_orders_fetch', $args);

        $query = new WP_Query($args);
        self::$totalPages = $query->max_num_pages;

        foreach ($query->posts as $order) {
            $ordersList[] = new WC_Order($order->ID);
        }

        return $ordersList;
    }

    /**
     * Fetch orders from the new HPOS system
     *
     * @see https://github.com/woocommerce/woocommerce/wiki/HPOS:-new-order-querying-APIs
     * @see https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters
     *
     * @return array
     */
    private static function getAllNewSystem()
    {
        $args = [
            'post_status' => self::$ordersStatuses,
            'posts_per_page' => self::$limit,
            'paged' => self::$currentPage,
            'orderby' => 'date',
            'paginate' => true,
            'order' => 'DESC',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_molonies_sent',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => '_molonies_sent',
                    'value' => '0',
                    'compare' => '='
                ]
            ],
        ];

        $args = apply_filters('moloni_before_pending_orders_fetch', $args);

        $results = wc_get_orders($args);

        self::$totalPages = $results->max_num_pages;

        return $results->orders;
    }
}
