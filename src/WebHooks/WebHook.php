<?php

namespace MoloniES\WebHooks;

use MoloniES\API\Hooks;
use MoloniES\Error;
use MoloniES\Model;
use MoloniES\Storage;

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
        if (!Storage::$MOLONI_ES_COMPANY_ID) {
            return;
        }

        //Load required variables (Storage:$MOLONI_ES_COMPANY_ID)
        Model::defineValues();

        $ids = [];

        $variables = [
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