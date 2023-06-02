<?php

namespace MoloniES\Helpers;

use MoloniES\API\Hooks;
use MoloniES\Exceptions\APIExeption;
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
     * @throws APIExeption
     */
    public static function createHook(string $model, string $operation)
    {
        if (!isset(self::$routes[$model])) {
            return;
        }

        $url = get_site_url() . '/wp-json/' . self::$restApiNamespace . '/' . self::$routes[$model] . '/' . self::createHash();

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
     * @throws APIExeption
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

    //            Privates            //

    /**
     * Creates hash from company id
     *
     * @return string
     */
    private static function createHash(): string
    {
        return hash('md5', Storage::$MOLONI_ES_COMPANY_ID);
    }
}
