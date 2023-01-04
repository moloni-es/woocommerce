<?php

namespace MoloniES\Helpers;

use Automattic\WooCommerce\Utilities\OrderUtil;

class Context
{
    public static function isNewOrdersSystemEnabled()
    {
        if (class_exists(OrderUtil::class)) {
            return OrderUtil::custom_orders_table_usage_is_enabled();
        }

        return false;
    }
}