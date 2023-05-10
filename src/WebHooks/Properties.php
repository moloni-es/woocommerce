<?php

namespace MoloniES\WebHooks;

use Exception;
use MoloniES\API\PropertyGroups;
use MoloniES\Exceptions\Error;
use MoloniES\Log;
use MoloniES\Model;
use MoloniES\Start;

class Properties
{
    /**
     * Attributes constructor.
     */
    public function __construct()
    {
        //creates a new route that receives an argument named hash
        register_rest_route('moloni/v1', 'properties/(?P<hash>[a-f0-9]{32}$)', [
            'methods' => 'POST',
            'callback' => [$this, 'properties']
        ]);
    }

    /**
     * Handles data form WebHook
     * @param $requestData
     * @return void
     * @throws Error
     */
    public function properties($requestData)
    {
        try {
            $parameters = $requestData->get_params();
            Log::write(print_r($parameters, true));
            if ($parameters['model'] !== 'Property' || !Start::login(true) || !Model::checkHash($parameters['hash'])) {
                return;
            }

            $variables = [
                'search' => [
                    'field' => 'name',
                    'value' => __('Online Store', 'moloni_es')
                ]
            ];

            $moloniPropertyGroups = PropertyGroups::queryPropertyGroups($variables);

            switch ($parameters['operation']) {
                case 'create':
                    $this->add($moloniPropertyGroups, $parameters['propertyGroupId']);
                    break;
                case 'update':
                    $this->update($moloniPropertyGroups, $parameters['propertyGroupId']);
                    break;
            }
        } catch (Exception $exception) {
            echo json_encode(['valid' => 0, 'error' => $exception->getMessage()]);
            exit;
        }
    }

    /**
     * Add an property
     * @param $propertyGroup
     * @param $propertyId
     * @return string
     */
    public function add($propertyGroup, $propertyId)
    {
        $variables = [];
        return 'false';
    }

    /**
     * Update an property
     * @param $propertyGroup
     * @param $propertyId
     * @return void
     */
    public function update($propertyGroup, $propertyId)
    {
        $variables = [];
    }
}