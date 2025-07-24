<?php

namespace MoloniES;

use MoloniES\Tools\Logger;

class Storage
{
    public static $MOLONI_ES_SESSION_ID;
    public static $MOLONI_ES_ACCESS_TOKEN;
    public static $MOLONI_ES_COMPANY_ID;

    /**
     * Checks if new order system is being used
     *
     * @var bool
     */
    public static $USES_NEW_ORDERS_SYSTEM = false;

    /**
     * Logger instance
     *
     * @var Logger|null
     */
    public static $LOGGER;
}
