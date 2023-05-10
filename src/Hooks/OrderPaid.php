<?php

namespace MoloniES\Hooks;

use Exception;
use MoloniES\Controllers\Documents;
use MoloniES\Exceptions\Error;
use MoloniES\Log;
use MoloniES\Plugin;
use MoloniES\Services\Orders\CreateMoloniDocument;
use MoloniES\Start;

class OrderPaid
{

    public $parent;

    /**
     *
     * @param Plugin $parent
     */
    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('woocommerce_order_status_completed', [$this, 'documentCreate']);
    }

    public function documentCreate($orderId)
    {
        try {
            if (Start::login(true) && defined("INVOICE_AUTO") && INVOICE_AUTO) {
                Log::write(__("Automatically generating the order document",'moloni_es') . ': ' . $orderId);

                $service = new CreateMoloniDocument($orderId);
                $service->run();

                Log::write(__("Order document created successfully",'moloni_es') . ': ' . $orderId);
            }
        } catch (Error $ex) {
            Log::write(__("There was an error generating the document: ",'moloni_es') . strip_tags($ex->getDecodedMessage()));
        } catch (Exception $ex) {
            Log::write(__("Fatal error: ",'moloni_es') . $ex->getMessage());
        }
    }

}
