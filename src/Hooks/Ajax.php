<?php

namespace MoloniES\Hooks;

use Exception;
use MoloniES\Exceptions\DocumentError;
use MoloniES\Exceptions\DocumentWarning;
use MoloniES\Plugin;
use MoloniES\Services\Exports\ExportProducts;
use MoloniES\Services\Exports\ExportStockChanges;
use MoloniES\Services\Imports\ImportProducts;
use MoloniES\Services\Imports\ImportStockChanges;
use MoloniES\Services\Orders\CreateMoloniDocument;
use MoloniES\Services\Orders\DiscardOrder;
use MoloniES\Start;
use MoloniES\Storage;

class Ajax
{
    public $parent;

    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('wp_ajax_genInvoice', [$this, 'genInvoice']);
        add_action('wp_ajax_discardOrder', [$this, 'discardOrder']);
        add_action('wp_ajax_importStock', [$this, 'importStock']);
        add_action('wp_ajax_importProduct', [$this, 'importProduct']);
        add_action('wp_ajax_exportStock', [$this, 'exportStock']);
        add_action('wp_ajax_exportProduct', [$this, 'exportProduct']);
    }

    //             Publics             //

    public function genInvoice()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new CreateMoloniDocument((int)$_REQUEST['id']);
        $orderName = $service->getOrderNumber() ?? '';

        try {
            $service->run();

            $response = [
                'valid' => 1,
                'message' => sprintf(__('Document %s successfully inserted', 'moloni_es'), $service->getOrderNumber())
            ];
        } catch (DocumentWarning $e) {
            $message = sprintf(__('There was an warning when generating the document (%s)'), $orderName);
            $message .= ' </br>';
            $message .= $e->getMessage();

            Storage::$LOGGER->alert(
                $message,
                [
                    'message' => $e->getMessage(),
                    'request' => $e->getData()
                ]
            );

            $response = ['valid' => 1, 'message' => $e->getMessage(), 'data' => $e->getData()];
        } catch (DocumentError $e) {
            $message = sprintf(__('There was an error when generating the document (%s)'), $orderName);
            $message .= ' </br>';
            $message .= strip_tags($e->getMessage());

            Storage::$LOGGER->error(
                $message,
                [
                    'message' => $e->getMessage(),
                    'request' => $e->getData()
                ]
            );

            $response = ['valid' => 0, 'message' => $e->getMessage(), 'data' => $e->getData()];
        } catch (Exception $e) {
            Storage::$LOGGER->critical(__("Fatal error", 'moloni_es'), [
                'action' => 'bulk:document:create',
                'exception' => $e->getMessage()
            ]);

            $response = ['valid' => 0, 'message' => $e->getMessage()];
        }

        $this->sendJson($response);
    }

    public function discardOrder()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $response = [
            'valid' => 1
        ];

        $order = wc_get_order((int)$_REQUEST['id']);

        $service = new DiscardOrder($order);
        $service->run();
        $service->saveLog();

        $this->sendJson($response);
    }

    public function importStock()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new ImportStockChanges((int)$_REQUEST['page']);
        $service->run();

        $response = [
            'valid' => 1,
            'hasMore' => $service->getHasMore(),
            'totalResults' => $service->getTotalResults(),
            'currentPercentage' => $service->getCurrentPercentage()
        ];

        $this->sendJson($response);
    }

    public function importProduct()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new ImportProducts((int)$_REQUEST['page']);
        $service->run();

        $response = [
            'valid' => 1,
            'hasMore' => $service->getHasMore(),
            'totalResults' => $service->getTotalResults(),
            'currentPercentage' => $service->getCurrentPercentage()
        ];

        $this->sendJson($response);
    }

    public function exportStock()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new ExportStockChanges((int)$_REQUEST['page']);
        $service->run();

        $response = [
            'valid' => 1,
            'hasMore' => $service->getHasMore(),
            'processedProducts' => $service->getProcessedProducts()
        ];

        $this->sendJson($response);
    }

    public function exportProduct()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new ExportProducts((int)$_REQUEST['page']);
        $service->run();

        $response = [
            'valid' => 1,
            'hasMore' => $service->getHasMore(),
            'processedProducts' => $service->getProcessedProducts()
        ];

        $this->sendJson($response);
    }

    //             Privates             //

    private function isAuthed(): bool
    {
        return Start::login(true);
    }

    /**
     * Return and stop execution afterward.
     *
     * @see https://developer.wordpress.org/reference/hooks/wp_ajax_action/
     *
     * @param array $data
     * @return void
     */
    private function sendJson(array $data)
    {
        wp_send_json($data);
        wp_die();
    }
}
