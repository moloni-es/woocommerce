<?php

namespace MoloniES\WebHooks;

use Exception;
use MoloniES\API\Products as ApiProducts;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\SyncLogsType;
use MoloniES\Exceptions\Error;
use MoloniES\Exceptions\WebhookException;
use MoloniES\Helpers\ProductAssociations;
use MoloniES\Helpers\SyncLogs;
use MoloniES\Services\WcProduct\Create\CreateChildProduct;
use MoloniES\Services\WcProduct\Create\CreateParentProduct;
use MoloniES\Services\WcProduct\Create\CreateSimpleProduct;
use MoloniES\Services\WcProduct\Stock\SyncProductStock;
use MoloniES\Services\WcProduct\Update\UpdateChildProduct;
use MoloniES\Services\WcProduct\Update\UpdateParentProduct;
use MoloniES\Services\WcProduct\Update\UpdateSimpleProduct;
use MoloniES\Start;
use MoloniES\Storage;

class Products
{
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
        try {
            $parameters = $requestData->get_params();

            /** Model has to be 'Product', needs to be logged in and received hash has to match logged in company id hash */
            if ($parameters['model'] !== 'Product' || !Start::login(true) || !$this->checkHash($parameters['hash'])) {
                return;
            }

            $productId = (int)sanitize_text_field($parameters['productId']);

            if (!$this->shouldRunHook($productId)) {
                return;
            }

            $this->fetchMoloniProduct($productId);

            if (!$this->isProductValid()) {
                return;
            }

            //switch between operations
            switch ($parameters['operation']) {
                case 'create':
                    $this->onCreate();
                    break;
                case 'update':
                    $this->onUpdate();
                    break;
                case 'stockChanged':
                    $this->onStockUpdate();
                    break;
            }

            $this->reply();
        } catch (WebhookException $exception) {
            $this->reply(0, $exception->getMessage());
        } catch (Exception $exception) {
            Storage::$LOGGER->critical(__('Fatal error', 'moloni_es'), [
                'message' => $exception->getMessage()
            ]);

            $this->reply(0, $exception->getMessage());
        }
    }

    //            Actions            //

    private function onCreate()
    {
        if (!$this->shouldSyncProduct()) {
            return;
        }

        $wcProduct = $this->fetchWcProduct($this->moloniProduct);

        if (!empty($wcProduct)) {
            return;
        }

        if ($this->moloniProductHasVariants()) {
            $service = new CreateParentProduct($this->moloniProduct);
            $service->run();
            $service->saveLog();

            $wcParentProduct = $service->getWcProduct();

            foreach ($this->moloniProduct['variants'] as $variant) {
                if ((int)$variant['visible'] === Boolean::NO) {
                    continue;
                }

                $service = new CreateChildProduct($this->moloniProduct, $wcParentProduct);
                $service->run();
                $service->saveLog();
            }
        } else {
            $service = new CreateSimpleProduct($this->moloniProduct);
            $service->run();
            $service->saveLog();
        }
    }

    private function onUpdate()
    {
        if (!$this->shouldSyncProduct()) {
            return;
        }

        $wcProduct = $this->fetchWcProduct($this->moloniProduct);

        if (empty($wcProduct) || SyncLogs::hasTimeout(SyncLogsType::WC_PRODUCT, $wcProduct->get_id())) {
            return;
        }

        /** Both need to be the same kind */
        if ($this->moloniProductHasVariants() !== $wcProduct->has_child()) {
            return;
        }

        if ($this->moloniProductHasVariants()) {
            $service = new UpdateParentProduct($this->moloniProduct, $wcProduct);
            $service->run();
            $service->saveLog();

            foreach ($this->moloniProduct['variants'] as $variant) {
                if ((int)$variant['visible'] === Boolean::NO) {
                    continue;
                }

                $wcProductVariation = $this->fetchWcProductVariation($variant);

                if ($wcProductVariation->get_parent_id() !== $wcProduct->get_id()) {
                    continue;
                }

                if (empty($wcProductVariation)) {
                    $service = new CreateChildProduct($this->moloniProduct, $wcProduct);
                } else {
                    $service = new UpdateChildProduct($this->moloniProduct, $wcProductVariation, $wcProduct);
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

    private function onStockUpdate()
    {
        if (!$this->shouldSyncStock()) {
            return;
        }

        $wcProduct = $this->fetchWcProduct($this->moloniProduct);

        if (empty($wcProduct)) {
            return;
        }

        /** Both need to be the same kind */
        if ($this->moloniProductHasVariants() !== $wcProduct->has_child()) {
            return;
        }

        if ($this->moloniProductHasVariants()) {
            foreach ($this->moloniProduct['variants'] as $variant) {
                if ((int)$variant['visible'] === Boolean::NO || (int)$variant['hasStock'] === Boolean::NO) {
                    continue;
                }

                $wcProductVariation = $this->fetchWcProductVariation($variant);

                if (empty($wcProductVariation) || !$wcProductVariation->managing_stock()) {
                    continue;
                }

                if ($wcProductVariation->get_parent_id() !== $wcProduct->get_id()) {
                    continue;
                }

                $service = new SyncProductStock($variant, $wcProductVariation);
                $service->run();
                $service->saveLog();
            }
        } else {
            if ((int)$this->moloniProduct['hasStock'] === Boolean::NO || !$wcProduct->managing_stock()) {
                return;
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
     * @throws WebhookException
     */
    private function fetchMoloniProduct(int $productId)
    {
        try {
            $query = ApiProducts::queryProduct([
                'productId' => $productId
            ]);
            $moloniProduct = $query['data']['product']['data'] ?? [];
        } catch (Error $e) {
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

    private function fetchWcProductVariation(array $moloniVariant)
    {
        /** Fetch by our associaitons table */

        $association = ProductAssociations::findByMoloniId($moloniVariant['productId']);

        if (!empty($association)) {
            $wcProduct = wc_get_product($association['wc_product_id']);

            if (!empty($wcProduct)) {
                return $wcProduct;
            }

            ProductAssociations::deleteById($association['id']);
        }

        /** Fetch by reference */

        $wcProductId = wc_get_product_id_by_sku($moloniVariant['reference']);

        if ($wcProductId > 0) {
            return wc_get_product($wcProductId);
        }

        /** Fetch by attribute match */

        // todo: this

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

    private function shouldRunHook(int $productId): bool
    {
        if (SyncLogs::hasTimeout(SyncLogsType::MOLONI_PRODUCT, $productId)) {
            return false;
        }

        SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT, $productId);

        return true;
    }

    /**
     * @throws WebhookException
     */
    private function isProductValid(): bool
    {
        /** Product not found */
        if (empty($this->moloniProduct)) {
            throw new WebhookException('Moloni product not found!');
        }

        /** We only want to update the main product */
        if ($this->moloniProduct['parent'] !== null) {
            throw new WebhookException('Product is variant, will be skipped!');
        }

        /** Do not sync shipping product */
        if (in_array(strtolower($this->moloniProduct['reference']), ['shipping', 'envio', 'envío', 'fee', 'tarifa'])) {
            throw new WebhookException('Product reference blacklisted!');
        }

        return true;
    }

    private function moloniProductHasVariants(): bool
    {
        return !empty($this->moloniProduct['variants']);
    }
}
