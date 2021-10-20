<?php

namespace MoloniES;

use MoloniES\Controllers\Documents;

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
                $orderId = (int)$_REQUEST['id'];

                try {
                    $document = new Documents($orderId);
                    $document->isHook = true;
                    $document->createDocument();

                    if (!$document->getError()) {
                        wp_send_json(['valid' => 1, 'message' => sprintf(__('Document %s successfully inserted','moloni_es'), $document->order->get_order_number())]);
                    }

                    wp_send_json([
                        'valid' => 0,
                        'message' => $document->getError()->getDecodedMessage(),
                        'description' => $document->getError()->getError()
                    ]);
                } catch (Error $e) {
                    wp_send_json(['valid' => 0, 'message' => $e->getMessage(), 'description' => $e->getError()]);
                }
            }
        } catch (Error $e) {
            wp_send_json(['valid' => 0, 'message' => $e->getMessage(), 'description' => $e->getError()]);
        }
    }
}
