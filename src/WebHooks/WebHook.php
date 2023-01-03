<?php

namespace MoloniES\WebHooks;

use MoloniES\API\Hooks;
use MoloniES\Error;
use MoloniES\Model;

class WebHook
{
    /**
     * Plugin rest api namespace
     * @var string
     */
    public static $restApiNamespace = 'moloni/v1';

    /**
     * Used routes
     * (only used in foreach on create and delete hooks)
     * ([route => Moloni Model])
     * @var string[]
     */
    public static $routes = [
        'products' => 'Product'
    ];//'properties' => 'Property'

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

    /**
     * Create hook in moloni
     * @throws Error
     */
    public static function createHooks()
    {
        self::deleteHooks(); //prevent multiple hooks doing the same

        $variables = [
            'companyId' => (int)MOLONIES_COMPANY_ID,
            'data' => []
        ];

        foreach (self::$routes as $route => $model) {
            $variables['data'] = [
                "model" => $model,
                "url" => get_site_url() . '/wp-json/' . self::$restApiNamespace . '/' . $route . '/' . Model::createHash()
            ];

            Hooks::mutationHookCreate($variables);
        }

    }

    /**
     * Deletes the created hooks
     *
     * @throws Error
     */
    public static function deleteHooks()
    {
        if (!defined('MOLONIES_COMPANY_ID')) {
            return;
        }

        //Load required variables (MOLONIES_COMPANY_ID)
        Model::defineValues();

        $ids = [];

        $variables = [
            'companyId' => (int)MOLONIES_COMPANY_ID,
            'data' => [
                'search' => [
                    'field' => 'url',
                    'value' => get_site_url() . '/wp-json/'
                ]
            ]
        ];

        $query = Hooks::queryHooks($variables);

        foreach ($query as $hook) {
            $ids[] = $hook['hookId'];
        }

        unset($variables['data']);
        $variables['hookId'] = $ids;

        Hooks::mutationHookDelete($variables);
    }
}