<?php

namespace MoloniES\WebHooks;

use MoloniES\Exceptions\HelperException;
use MoloniES\Services\MoloniProduct\Helpers\Variants\ParseProductProperties;
use MoloniES\Services\WcProduct\Helpers\Variations\FindVariation;
use Exception;
use MoloniES\API\Products as ApiProducts;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\SyncLogsType;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\Core\MoloniException;
use MoloniES\Exceptions\WebhookException;
use MoloniES\Helpers\References;
use MoloniES\Services\WcProduct\Create\CreateChildProduct;
use MoloniES\Services\WcProduct\Create\CreateParentProduct;
use MoloniES\Services\WcProduct\Create\CreateSimpleProduct;
use MoloniES\Services\WcProduct\Stock\SyncProductStock;
use MoloniES\Services\WcProduct\Update\UpdateChildProduct;
use MoloniES\Services\WcProduct\Update\UpdateParentProduct;
use MoloniES\Services\WcProduct\Update\UpdateSimpleProduct;
use MoloniES\Start;
use MoloniES\Storage;
use MoloniES\Tools\ProductAssociations;
use MoloniES\Tools\SyncLogs;
use MoloniES\Traits\SettingsTrait;

class Products
{
    use SettingsTrait;

    /**
     * Moloni product
     *
     * @var array
     */
    private $moloniProduct = [];

    /**
     * Products constructor.
     */
    public function __construct()
    {
        //create a new route
        register_rest_route('moloni/v1', 'products/(?P<hash>[a-f0-9]{32}$)', [
            'methods' => 'POST',
            'callback' => [$this, 'products'],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * Handles data form WebHook
     *
     * @param $requestData
     *
     * @return void
     */
    public function products($requestData)
    {
        $parameters = $requestData->get_params();
        $errorMessage = '';

        try {
            /** Model has to be 'Product', needs to be logged in and received hash has to match logged in company id hash */
            if ($parameters['model'] !== 'Product' || !Start::login(true) || !$this->checkHash($parameters['hash'])) {
                return;
            }

            $productId = (int)sanitize_text_field($parameters['productId']);

            $this->fetchMoloniProduct($productId);

            //switch between operations
            switch ($parameters['operation']) {
                case 'create':
                    $errorMessage = __('Error creating product in WooCommerce', 'moloni_es');

                    $this->onCreate();
                    break;
                case 'update':
                    $errorMessage = __('Error updating product in WooCommerce', 'moloni_es');

                    $this->onUpdate();
                    break;
                case 'stockChanged':
                    $errorMessage = __('Error updating stock in WooCommerce', 'moloni_es');

                    $this->onStockUpdate();
                    break;
            }

            $this->reply();
        } catch (MoloniException $exception) {
            $identifier = $this->moloniProduct['reference'] ?? "---";

            $errorMessage .= " ($identifier) ";
            $errorMessage .= '</br>';
            $errorMessage .= $exception->getMessage();

            if (!in_array(substr($errorMessage, -1), ['.', '!', '?'])) {
                $errorMessage .= '.';
            }

            Storage::$LOGGER->error($errorMessage, [
                'tag' => 'webhook:product:error',
                'message' => $exception->getMessage(),
                'extra' => [
                    'parameters' => $parameters,
                    'data' => $exception->getData(),
                ]
            ]);

            $this->reply(0, $exception->getMessage());
        } catch (Exception $exception) {
            Storage::$LOGGER->critical(__('Fatal error', 'moloni_es'), [
                    'tag' => 'webhook:product:fatalerror',
                    'message' => $exception->getMessage(),
                    'extra' => [
                        'parameters' => $parameters,
                    ]
                ]
            );

            $this->reply(0, $exception->getMessage());
        }
    }

    //            Actions            //

    /**
     * Create action
     *
     * @throws WebhookException
     */
    private function onCreate()
    {
        if (!$this->shouldSyncProduct()) {
            return;
        }

        $this->validateMoloniProduct();

        $wcProduct = $this->fetchWcProduct($this->moloniProduct);

        if (!empty($wcProduct)) {
            throw new WebhookException(__('Product already exists', 'moloni_es'));
        }

        if (SyncLogs::hasTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $this->moloniProduct['productId'])) {
            return;
        }

        SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $this->moloniProduct['productId']);

        if ($this->moloniProductHasVariants()) {
            $service = new CreateParentProduct($this->moloniProduct);
            $service->run();
            $service->saveLog();

            $wcParentProduct = $service->getWcProduct();

            foreach ($this->moloniProduct['variants'] as $variant) {
                if ((int)$variant['visible'] === Boolean::NO) {
                    continue;
                }

                $service = new CreateChildProduct($variant, $wcParentProduct);
                $service->run();
                $service->saveLog();
            }
        } else {
            $service = new CreateSimpleProduct($this->moloniProduct);
            $service->run();
            $service->saveLog();
        }
    }

    /**
     * On Moloni product update action
     *
     * @throws HelperException|WebhookException
     */
    private function onUpdate()
    {
        if (!$this->shouldSyncProduct()) {
            return;
        }

        $this->validateMoloniProduct();

        $wcProduct = $this->fetchWcProduct($this->moloniProduct);

        if (empty($wcProduct)) {
            throw new WebhookException(__('Product not found', 'moloni_es'));
        }

        /** Both need to be the same kind */
        if ($this->moloniProductHasVariants() !== $wcProduct->is_type('variable')) {
            throw new WebhookException(__('Product types do not match', 'moloni_es'));
        }

        if (SyncLogs::hasTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id()) ||
            SyncLogs::hasTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $this->moloniProduct['productId'])) {
            return;
        }

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id());
        SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $this->moloniProduct['productId']);

        if ($this->moloniProductHasVariants()) {
            $service = new UpdateParentProduct($this->moloniProduct, $wcProduct);
            $service->run();
            $service->saveLog();

            $wcParentAttributes = (new ParseProductProperties($wcProduct))->handle();

            foreach ($this->moloniProduct['variants'] as $variant) {
                if ((int)$variant['visible'] === Boolean::NO) {
                    continue;
                }

                $wcProductVariation = (new FindVariation($wcParentAttributes, $variant))->run();

                if (empty($wcProductVariation)) {
                    $service = new CreateChildProduct($variant, $wcProduct);
                } else {
                    $service = new UpdateChildProduct($variant, $wcProductVariation, $wcProduct);
                }

                $service->run();
                $service->saveLog();
            }
        } else {
            $service = new UpdateSimpleProduct($this->moloniProduct, $wcProduct);
            $service->run();
            $service->saveLog();
        }
    }

    /**
     * On Moloni stock update
     *
     * @throws HelperException|WebhookException
     */
    private function onStockUpdate()
    {
        if (!$this->shouldSyncStock()) {
            return;
        }

        $this->validateMoloniProduct();

        $wcProduct = $this->fetchWcProduct($this->moloniProduct);

        if (empty($wcProduct)) {
            throw new WebhookException(__('Product not found', 'moloni_es'));
        }

        if (SyncLogs::hasTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProduct->get_id()) ||
            SyncLogs::hasTimeout(SyncLogsType::MOLONI_PRODUCT_STOCK, $this->moloniProduct['productId'])) {
            return;
        }

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProduct->get_id());
        SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_STOCK, $this->moloniProduct['productId']);

        /** Both need to be the same kind */
        if ($this->moloniProductHasVariants() !== $wcProduct->is_type('variable')) {
            throw new WebhookException(__('Product types do not match', 'moloni_es'));
        }

        if ($this->moloniProductHasVariants()) {

            $wcParentAttributes = (new ParseProductProperties($wcProduct))->handle();

            foreach ($this->moloniProduct['variants'] as $variant) {
                if ((int)$variant['visible'] === Boolean::NO || (int)$variant['hasStock'] === Boolean::NO) {
                    continue;
                }

                $wcProductVariation = (new FindVariation($wcParentAttributes, $variant))->run();

                if (empty($wcProductVariation) || !$wcProductVariation->managing_stock()) {
                    continue;
                }

                $service = new SyncProductStock($variant, $wcProductVariation);
                $service->run();
                $service->saveLog();
            }
        } else {
            if ((int)$this->moloniProduct['hasStock'] === Boolean::NO || !$wcProduct->managing_stock()) {
                throw new WebhookException(__('Product does not manage stock', 'moloni_es'));
            }

            $service = new SyncProductStock($this->moloniProduct, $wcProduct);
            $service->run();
            $service->saveLog();
        }
    }

    //            Privates            //

    private function reply(?int $valid = 1, ?string $message = ''): void
    {
        echo json_encode(['valid' => $valid, 'message' => $message]);
    }

    //            Auxiliary            //

    /**
     * Fetch Moloni product
     *
     * @throws WebhookException
     */
    private function fetchMoloniProduct(int $productId)
    {
        try {
            $query = ApiProducts::queryProduct([
                'productId' => $productId
            ]);

            $moloniProduct = $query['data']['product']['data'] ?? [];
        } catch (APIExeption $e) {
            throw new WebhookException($e->getMessage());
        }

        $this->moloniProduct = $moloniProduct;
    }

    private function fetchWcProduct(array $moloniProduct)
    {
        /** Fetch by our associaitons table */

        $association = ProductAssociations::findByMoloniId($moloniProduct['productId']);

        if (!empty($association)) {
            $wcProduct = wc_get_product($association['wc_product_id']);

            if (!empty($wcProduct)) {
                return $wcProduct;
            }

            ProductAssociations::deleteById($association['id']);
        }

        /** Fetch by reference */

        $wcProductId = wc_get_product_id_by_sku($moloniProduct['reference']);

        if ($wcProductId > 0) {
            return wc_get_product($wcProductId);
        }

        return null;
    }

    /**
     * Checks if hash with company id hash
     *
     * @param string $hash
     *
     * @return bool
     */
    private function checkHash(string $hash): bool
    {
        return hash('md5', Storage::$MOLONI_ES_COMPANY_ID) === $hash;
    }

    //            Verifications            //

    private function shouldSyncProduct(): bool
    {
        return defined('HOOK_PRODUCT_SYNC') && (int)HOOK_PRODUCT_SYNC === Boolean::YES;
    }

    private function shouldSyncStock(): bool
    {
        return defined('HOOK_STOCK_SYNC') && (int)HOOK_STOCK_SYNC === Boolean::YES;
    }

    private function moloniProductHasVariants(): bool
    {
        return !empty($this->moloniProduct['variants']);
    }

    /**
     * Validate Moloni product data
     *
     * @throws WebhookException
     */
    private function validateMoloniProduct()
    {
        /** Product not found */
        if (empty($this->moloniProduct)) {
            throw new WebhookException(__('Moloni product not found', 'moloni_es'));
        }

        /** We only want to update the main product */
        if ($this->moloniProduct['parent'] !== null) {
            throw new WebhookException(__('Product is variant, will be skipped', 'moloni_es'));
        }

        /** Do not sync shipping product */
        if (References::isIgnoredReference($this->moloniProduct['reference'])) {
            throw new WebhookException(__('Product reference blacklisted', 'moloni_es'));
        }

        /** Do not sync product with varianst if settings is not set */
        if ($this->moloniProductHasVariants() &&
            (!defined('SYNC_PRODUCTS_WITH_VARIANTS') || (int)SYNC_PRODUCTS_WITH_VARIANTS === Boolean::NO)) {
            throw new WebhookException(__('Synchronization of products with variants is disabled', 'moloni_es'));
        }
    }
}
