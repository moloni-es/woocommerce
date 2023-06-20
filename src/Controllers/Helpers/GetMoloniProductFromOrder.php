<?php

namespace MoloniES\Controllers\Helpers;

use WC_Product;
use WC_Order_Item_Product;
use MoloniES\Traits\SettingsTrait;
use MoloniES\API\Products;
use MoloniES\Enums\SyncLogsType;
use MoloniES\Tools;
use MoloniES\Tools\SyncLogs;
use MoloniES\Tools\ProductAssociations;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\HelperException;
use MoloniES\Exceptions\ServiceException;
use MoloniES\Services\MoloniProduct\Create\CreateSimpleProduct;
use MoloniES\Services\MoloniProduct\Create\CreateVariantProduct;
use MoloniES\Services\MoloniProduct\Update\UpdateVariantProduct;
use MoloniES\Services\MoloniProduct\Helpers\Variants\ParseProductProperties;
use MoloniES\Services\MoloniProduct\Helpers\Variants\FindVariantByProperties;

class GetMoloniProductFromOrder
{
    use SettingsTrait;

    private $orderProduct;

    public function __construct(WC_Order_Item_Product $orderProduct)
    {
        $this->orderProduct = $orderProduct;
    }

    /**
     * Handler
     *
     * @throws HelperException
     */
    public function handle(): int
    {
        $wcProductId = $this->orderProduct->get_product_id();
        $wcVariationId = $this->orderProduct->get_variation_id();

        if ($wcVariationId > 0) {
            if ($this->isSyncProductWithVariantsActive()) {
                return (int)$this->getVariationProduct($wcProductId, $wcVariationId);
            } else {
                return (int)$this->getProduct($wcVariationId);
            }
        }

        return (int)$this->getProduct($wcProductId);
    }

    //          Privates          //

    /**
     * Find product in Moloni
     *
     * @throws HelperException
     */
    private function getProduct(int $wcProductId)
    {
        $moloniProduct = $this->findInAssociationsTable($wcProductId);

        if (!empty($moloniProduct)) {
            return (int)$moloniProduct['productId'];
        }

        $wcProduct = wc_get_product($wcProductId);

        if (empty($wcProduct)) {
            throw new HelperException(__('Order products were deleted.', 'moloni_es'));
        }

        $moloniProduct = $this->getByReference($wcProduct);

        if (!empty($moloniProduct)) {
            return (int)$moloniProduct['productId'];
        }

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProductId);

        /** Not found, lets create a new product */
        try {
            $service = new CreateSimpleProduct($wcProduct);
            $service->run();
            $service->saveLog();
        } catch (ServiceException $e) {
            throw new HelperException($e->getMessage(), $e->getData());
        }

        return $service->getMoloniProduct()['productId'];
    }

    /**
     * Find variation in Moloni
     *
     * @throws HelperException
     */
    private function getVariationProduct(int $wcProductId, int $wcVariationId)
    {
        $moloniProduct = $this->findInAssociationsTable($wcVariationId);

        if (!empty($moloniProduct)) {
            return $moloniProduct['productId'];
        }

        $wcProduct = wc_get_product($wcProductId);
        $wcVariation = wc_get_product($wcVariationId);

        if (empty($wcProduct) || empty($wcVariation)) {
            throw new HelperException(__('Order products were deleted.', 'moloni_es'));
        }

        $moloniProduct = $this->getByReference($wcVariation);

        if (!empty($moloniProduct)) {
            return $moloniProduct['productId'];
        }

        $moloniProduct = $this->findByParentProduct($wcProduct, $wcVariation);

        if (!empty($moloniProduct)) {
            return $moloniProduct['productId'];
        }

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProductId);

        try {
            $service = new CreateVariantProduct($wcProduct);
            $service->run();
            $service->saveLog();
        } catch (ServiceException $e) {
            throw new HelperException($e->getMessage(), $e->getData());
        }

        return $service->getVariant($wcVariationId)['productId'];
    }

    //          Auxiliary          //

    /**
     * Find product using the association table
     *
     * @throws HelperException
     */
    private function findInAssociationsTable($wcId): array
    {
        $association = ProductAssociations::findByWcId($wcId);

        /** Association found, let's fetch by ID */
        if (!empty($association)) {
            $byId = $this->getById($association['ml_product_id']);

            if (!empty($byId)) {
                return $byId;
            }

            ProductAssociations::deleteById($association['id']);
        }

        return [];
    }

    /**
     * Get product parent
     *
     * @throws HelperException
     */
    private function findByParentProduct(WC_Product $wcProduct, WC_Product $wcVariation): array
    {
        $moloniProduct = $this->findInAssociationsTable($wcProduct->get_id());

        if (empty($moloniProduct)) {
            $moloniProduct = $this->getByReference($wcProduct);
        }

        /** Product really does not exist, can return */
        if (empty($moloniProduct)) {
            return [];
        }

        /** For some reason the prodcut is simple in Moloni, use that one */
        if (empty($moloniProduct['variants'])) {
            return $moloniProduct;
        }

        $wcProductAttributes = (new ParseProductProperties($wcProduct))->handle();
        $wcTargetProductAttributes = $wcProductAttributes[$wcVariation->get_id()] ?? [];

        /** Let's find the variation in the Moloni product */
        $variant = (new FindVariantByProperties($wcTargetProductAttributes, $moloniProduct))->handle();

        /** Variant already exists in Moloni, use that one */
        if (!empty($variant)) {
            return $variant;
        }

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id());
        SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $moloniProduct['productId']);

        /** Moloni product is outdated, let's update it */
        try {
            $service = new UpdateVariantProduct($wcProduct, $moloniProduct);
            $service->run();
            $service->saveLog();
        } catch (ServiceException $e) {
            throw new HelperException($e->getMessage(), $e->getData());
        }

        $variant = $service->getVariant($wcVariation->get_id());

        if (empty($variant)) {
            throw new HelperException(__('Could not find variant after update.', 'moloni_es'));
        }

        return $variant;
    }

    //          REQUESTS          //

    /**
     * Get product by ID
     *
     * @throws HelperException
     */
    private function getById(int $productId): array
    {
        $variables = [
            'productId' => $productId
        ];

        try {
            $byId = Products::queryProduct($variables);
        } catch (APIExeption $e) {
            throw new HelperException(
                __('Error fetching products', 'moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData(),
                ]
            );
        }

        return $byId['data']['product']['data'] ?? [];
    }

    /**
     * Get product by reference
     *
     * @throws HelperException
     */
    private function getByReference(WC_Product $wcProduct): array
    {
        $reference = $wcProduct->get_sku();

        if (empty($reference)) {
            $reference = Tools::createReferenceFromString($wcProduct->get_name());
        }

        $variables = [
            'options' => [
                'filter' => [
                    [
                        'field' => 'reference',
                        'comparison' => 'eq',
                        'value' => $reference,
                    ],
                    [
                        'field' => 'visible',
                        'comparison' => 'in',
                        'value' => '[0, 1]'
                    ]
                ],
                "includeVariants" => true
            ]
        ];

        try {
            $query = Products::queryProducts($variables);
        } catch (APIExeption $e) {
            throw new HelperException(
                __('Error fetching products', 'moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData(),
                ]
            );
        }

        $byReference = $query['data']['products']['data'] ?? [];

        if (!empty($byReference) && isset($byReference[0]['productId'])) {
            return $byReference[0];
        }

        return [];
    }
}
