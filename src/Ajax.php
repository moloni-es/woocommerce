<?php

namespace MoloniES;

use MoloniES\Controllers\Documents;
use MoloniES\Exceptions\Error;
use MoloniES\Exceptions\DocumentWarning;
use MoloniES\Services\Orders\CreateMoloniDocument;

class Ajax
{
    public $parent;

    /**
     *
     * @param Plugin $parent
     */
    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;
        add_action('wp_ajax_genInvoice', [$this, 'genInvoice']);
    }

    public function genInvoice()
    {
        if (!Start::login(true)) {
            return;
        }

        $service = new CreateMoloniDocument((int)$_REQUEST['id']);
        $orderName = $service->getOrderNumber() ?? '';

        try {
            $service->run();

            wp_send_json([
                'valid' => 1,
                'message' => sprintf(__('Document %s successfully inserted', 'moloni_es'), $service->getOrderNumber())
            ]);
        } catch (DocumentWarning $e) {
            Storage::$LOGGER->alert(
                sprintf(__('There was an warning when generating the document (%s)'), $orderName),
                [
                    'message' => $e->getMessage(),
                    'request' => $e->getRequest()
                ]
            );

            wp_send_json(['valid' => 1, 'message' => $e->getMessage(), 'description' => $e->getError()]);
        } catch (Error $e) {
            Storage::$LOGGER->error(
                sprintf(__('There was an error when generating the document (%s)'), $orderName),
                [
                    'message' => $e->getMessage(),
                    'request' => $e->getRequest()
                ]
            );

            wp_send_json(['valid' => 0, 'message' => $e->getMessage(), 'description' => $e->getError()]);
        }
    }
}
