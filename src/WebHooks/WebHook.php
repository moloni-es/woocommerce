<?php

namespace MoloniES\WebHooks;

class WebHook
{
    /**
     * WebHooks constructor.
     */
    public function __construct()
    {
        //hooks the initiation of the Rest API
        add_action('rest_api_init', [$this, 'setWebHooks']);
    }

    /**
     * Starts all classes that create the routes for API calls
     */
    public function setWebHooks()
    {
        new Products();
        //new Properties(); //todo: endpoints missing from the API
    }
}