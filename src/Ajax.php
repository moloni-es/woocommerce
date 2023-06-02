<?php

namespace MoloniES;

use Exception;
use MoloniES\Exceptions\DocumentError;
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

            wp_send_json(['valid' => 1, 'message' => $e->getMessage(), 'data' => $e->getData()]);
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

            wp_send_json(['valid' => 0, 'message' => $e->getMessage(), 'data' => $e->getData()]);
        } catch (Exception $e) {
            Storage::$LOGGER->critical(__("Fatal error", 'moloni_es'), [
                'action' => 'bulk:document:create',
                'exception' => $e->getMessage()
            ]);

            wp_send_json(['valid' => 0, 'message' => $e->getMessage()]);
        }
    }
}
