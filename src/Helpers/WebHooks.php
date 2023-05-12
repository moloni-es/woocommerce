<?php

namespace MoloniES\Helpers;

use MoloniES\API\Hooks;
use MoloniES\Exceptions\Error;
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
     * Model => routes mapping
     *
     * @var string[]
     */
    private static $routes = [
        'Product' => 'products'
    ];

    /**
     * Create hook in moloni
     *
     * @param string $model
     * @param string $operation
     *
     * @throws Error
     */
    public static function createHook(string $model, string $operation)
    {
        if (!isset(self::$routes[$model])) {
            return;
        }

        $url = get_site_url() . '/wp-json/' . self::$restApiNamespace . '/' . self::$routes[$model] . '/' . Model::createHash();

        $variables['data'] = [
            'model' => $model,
            'url' => $url,
            'operation' => $operation
        ];

        Hooks::mutationHookCreate($variables);
    }

    /**
     * Deletes the created hooks
     *
     * @throws Error
     */
    public static function deleteHooks()
    {
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

        if (!empty($query) && is_array($query)) {
            foreach ($query as $hook) {
                $ids[] = $hook['hookId'];
            }

            Hooks::mutationHookDelete([
                'hookId' => $ids
            ]);
        }
    }
}