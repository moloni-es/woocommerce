<?php

namespace MoloniES;

use MoloniES\Controllers\Documents;
use MoloniES\Exceptions\Error;
use MoloniES\Services\Orders\CreateMoloniDocument;

class Ajax
{
    public $parent;

    /**
     *
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('wp_ajax_genInvoice', [$this, 'genInvoice']);
    }

    public function genInvoice()
    {
        try {
            if (Start::login(true)) {
                $service = new CreateMoloniDocument((int)$_REQUEST['id']);
                $service->run();

                wp_send_json([
                    'valid' => 1,
                    'message' => sprintf(__('Document %s successfully inserted', 'moloni_es'), $service->getOrderNumber())
                ]);
            }
        } catch (Error $e) {
            wp_send_json(['valid' => 0, 'message' => $e->getMessage(), 'description' => $e->getError()]);
        }
    }
}
