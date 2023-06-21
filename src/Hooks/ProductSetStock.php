<?php

namespace MoloniES\Hooks;

use Exception;
use WC_Product;
use MoloniES\Start;
use MoloniES\Plugin;
use MoloniES\Storage;
use MoloniES\API\Products;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\SyncLogsType;
use MoloniES\Traits\SettingsTrait;
use MoloniES\Tools\SyncLogs;
use MoloniES\Tools\ProductAssociations;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\HookException;
use MoloniES\Exceptions\Core\MoloniException;
use MoloniES\Services\MoloniProduct\Stock\SyncProductStock;
use MoloniES\Services\MoloniProduct\Helpers\Variants\ParseProductProperties;
use MoloniES\Services\MoloniProduct\Helpers\Variants\FindVariantByProperties;

class ProductSetStock
{
    use SettingsTrait;

    public $parent;

    /**
     * Constructor
     *
     * @see https://stackoverflow.com/questions/39294861/which-hooks-are-triggered-when-woocommerce-product-stock-is-updated
     */
    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('woocommerce_product_set_stock', [$this, 'woocommerceProductSetStock']);
        add_action('woocommerce_variation_set_stock', [$this, 'woocommerceVariationSetStock']);
    }

    public function woocommerceProductSetStock(WC_Product $wcProduct)
    {
        if (!Start::login(true) || !$this->shouldRunHook()) {
            return;
        }

        try {
            $this->validateWcProduct($wcProduct);

            $moloniProduct = $this->fetchProductFromMoloni($wcProduct);

            $this->validateMoloniProduct($moloniProduct);

            $service = new SyncProductStock($wcProduct, $moloniProduct);
            $service->run();
            $service->saveLog();
        } catch (MoloniException $e) {
            $message = __('Error synchronizing stock.', 'moloni_es');
            $message .= ' </br>';
            $message .= $e->getMessage();

            if (!in_array(substr($message, -1), ['.', '!', '?'])) {
                $message .= '.';
            }

            Storage::$LOGGER->error($message, [
                    'tag' => 'automatic:product:stock:error',
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        } catch (Exception $e) {
            Storage::$LOGGER->critical(__('Fatal error', 'moloni_es'), [
                'tag' => 'automatic:product:stock:fatalerror',
                'message' => $e->getMessage(),
                'extra' => [
                    'wcProductId' => $wcProduct->get_id(),
                ]
            ]);
        }
    }

    public function woocommerceVariationSetStock(WC_Product $wcVariation)
    {
        if (!Start::login(true) || !$this->shouldRunHook()) {
            return;
        }

        try {
            $this->validateWcProduct($wcVariation);

            $moloniProduct = $this->fetchProductFromMoloni($wcVariation);

            if (empty($moloniProduct)) {
                if ($this->isSyncProductWithVariantsActive()) {
                    $wcParent = wc_get_product($wcVariation->get_parent_id());

                    $possibleMoloniProduct = $this->fetchProductFromMoloni($wcParent);

                    if (!empty($possibleMoloniProduct)) {
                        if (empty($possibleMoloniProduct['variants'])) {
                            $moloniProduct = $possibleMoloniProduct;
                        } else {
                            $wcProductAttributes = (new ParseProductProperties($wcParent))->handle();
                            $wcTargetProductAttributes = $wcProductAttributes[$wcVariation->get_id()] ?? [];

                            $moloniProduct = (new FindVariantByProperties($wcTargetProductAttributes, $moloniProduct))->handle();
                        }
                    }
                }
            }

            $this->validateMoloniProduct($moloniProduct);

            $service = new SyncProductStock($wcVariation, $moloniProduct);
            $service->run();
            $service->saveLog();
        } catch (MoloniException $e) {
            $message = __('Error synchronizing stock.', 'moloni_es');
            $message .= ' </br>';
            $message .= $e->getMessage();

            if (!in_array(substr($message, -1), ['.', '!', '?'])) {
                $message .= '.';
            }

            Storage::$LOGGER->error($message, [
                    'tag' => 'automatic:variation:stock:error',
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        } catch (Exception $e) {
            Storage::$LOGGER->critical(__('Fatal error', 'moloni_es'), [
                'tag' => 'automatic:variation:stock:fatalerror',
                'message' => $e->getMessage(),
                'extra' => [
                    'wcProductId' => $wcVariation->get_id(),
                ]
            ]);
        }
    }

    //            Fetchs            //

    /**
     * Fetch Moloni Product
     *
     * @throws APIExeption
     */
    private function fetchProductFromMoloni(WC_Product $wcProduct): array
    {
        $association = ProductAssociations::findByWcId($wcProduct->get_id());

        if (!empty($association)) {
            $byId = Products::queryProduct(['productId' => $association['ml_product_id']]);
            $byId = $byId['data']['product']['data'] ?? [];

            if (!empty($byId)) {
                return $byId;
            }

            ProductAssociations::deleteById($association['id']);
        }

        $wcSku = $wcProduct->get_sku();

        if (empty($wcSku)) {
            return [];
        }

        $variables = [
            'options' => [
                'filter' => [
                    [
                        'field' => 'reference',
                        'comparison' => 'eq',
                        'value' => $wcProduct->get_sku(),
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

        $query = Products::queryProducts($variables);

        $byReference = $query['data']['products']['data'] ?? [];

        if (!empty($byReference) && isset($byReference[0]['productId'])) {
            return $byReference[0];
        }

        return [];
    }

    //          Auxiliary          //

    private function shouldRunHook(): bool
    {
        return defined('MOLONI_STOCK_SYNC') && (int)MOLONI_STOCK_SYNC === Boolean::YES;
    }

    /**
     * Verify some Moloni data
     *
     * @throws HookException
     */
    private function validateMoloniProduct(?array $moloniProduct = [])
    {
        if (empty($moloniProduct)) {
            throw new HookException(__('Product not found', 'moloni_es'));
        }

        if (!empty($moloniProduct['variants'])) {
            throw new HookException(__('Product types do not match', 'moloni_es'));
        }

        if ((int)$moloniProduct['hasStock'] === Boolean::NO) {
            throw new HookException(__('Product does not manage stock', 'moloni_es'));
        }
    }

    /**
     * Verify some WooCommerce data
     *
     * @throws HookException
     */
    private function validateWcProduct(WC_Product $wcProduct)
    {
        if ($wcProduct->is_type('variable')) {
            throw new HookException(__('Product types do not match', 'moloni_es'));
        }

        if ($wcProduct->get_status() === 'draft') {
            throw new HookException(__('Product is not published', 'moloni_es'));
        }

        if (SyncLogs::hasTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProduct->get_id())) {
            throw new HookException(__('Product has timeout', 'moloni_es'));
        }
    }
}
