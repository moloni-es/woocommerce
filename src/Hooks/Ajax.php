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

        add_action('wp_ajax_toolsImportStock', [$this, 'toolsImportStock']);
        add_action('wp_ajax_toolsImportProduct', [$this, 'toolsImportProduct']);
        add_action('wp_ajax_toolsExportStock', [$this, 'toolsExportStock']);
        add_action('wp_ajax_toolsExportProduct', [$this, 'toolsExportProduct']);
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

            Storage::$LOGGER->alert($message, [
                    'tag' => 'ajax:document:create:warning',
                    'message' => $e->getMessage(),
                    'request' => $e->getData()
                ]
            );

            $response = ['valid' => 1, 'message' => $e->getMessage(), 'data' => $e->getData()];
        } catch (DocumentError $e) {
            $message = sprintf(__('There was an error when generating the document (%s)'), $orderName);
            $message .= ' </br>';
            $message .= strip_tags($e->getMessage());

            Storage::$LOGGER->error($message, [
                    'tag' => 'ajax:document:create:error',
                    'message' => $e->getMessage(),
                    'request' => $e->getData()
                ]
            );

            $response = ['valid' => 0, 'message' => $e->getMessage(), 'data' => $e->getData()];
        } catch (Exception $e) {
            Storage::$LOGGER->critical(__("Fatal error", 'moloni_es'), [
                'tag' => 'ajax:document:create:fatalerror',
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


    public function toolsImportStock()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new ImportStockChanges((int)$_REQUEST['page']);
        $service->run();

        $response = [
            'valid' => 1,
            'overlayContent' => '',
            'hasMore' => $service->getHasMore(),
            'totalResults' => $service->getTotalResults(),
            'currentPercentage' => $service->getCurrentPercentage()
        ];

        $response['overlayContent'] = $this->loadModalContent($response);

        $this->sendJson($response);
    }

    public function toolsImportProduct()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new ImportProducts((int)$_REQUEST['page']);
        $service->run();

        $response = [
            'valid' => 1,
            'overlayContent' => '',
            'hasMore' => $service->getHasMore(),
            'totalResults' => $service->getTotalResults(),
            'currentPercentage' => $service->getCurrentPercentage()
        ];

        $response['overlayContent'] = $this->loadModalContent($response);

        $this->sendJson($response);
    }

    public function toolsExportStock()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new ExportStockChanges((int)$_REQUEST['page']);
        $service->run();

        $response = [
            'valid' => 1,
            'overlayContent' => '',
            'hasMore' => $service->getHasMore(),
            'totalResults' => $service->getTotalResults(),
            'currentPercentage' => $service->getCurrentPercentage()
        ];

        $response['overlayContent'] = $this->loadModalContent($response);

        $this->sendJson($response);
    }

    public function toolsExportProduct()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new ExportProducts((int)$_REQUEST['page']);
        $service->run();

        $response = [
            'valid' => 1,
            'overlayContent' => '',
            'hasMore' => $service->getHasMore(),
            'totalResults' => $service->getTotalResults(),
            'currentPercentage' => $service->getCurrentPercentage()
        ];

        $response['overlayContent'] = $this->loadModalContent($response);

        $this->sendJson($response);
    }

    //             Privates             //

    private function isAuthed(): bool
    {
        return Start::login(true);
    }

    /**
     * Load tools modal content
     *
     * @see https://wpadmin.bracketspace.com/
     */
    private function loadModalContent($data)
    {
        ob_start();

        include MOLONI_ES_TEMPLATE_DIR . 'Modals/Tools/Blocks/ActionModalContent.php';

        return ob_get_clean();
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
