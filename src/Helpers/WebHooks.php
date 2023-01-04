<?php

namespace MoloniES\Helpers;

use MoloniES\API\Hooks;
use MoloniES\Error;
use MoloniES\Model;
use MoloniES\Storage;

class WebHooks
{
    /**
     * Plugin rest api namespace
     * @var string
     */
    private static $restApiNamespace = 'moloni/v1';

    /**
     * Used routes
     * (only used in foreach on create and delete hooks)
     * ([route => Moloni Model])
     * @var string[]
     */
    private static $routes = [
        'products' => 'Product'
    ];//'properties' => 'Property'

    /**
     * Create hook in moloni
     * @throws Error
     */
    public static function createHooks()
    {
        if (!Storage::$MOLONI_ES_COMPANY_ID) {
            return;
        }

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