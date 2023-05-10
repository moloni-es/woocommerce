<?php

namespace MoloniES\Hooks;

use Exception;
use MoloniES\Exceptions\Warning;
use MoloniES\Log;
use MoloniES\Notice;
use MoloniES\Services\Mails\DocumentFailed;
use MoloniES\Services\Mails\DocumentWarning;
use MoloniES\Start;
use MoloniES\Plugin;
use MoloniES\Exceptions\Error;
use MoloniES\Services\Orders\CreateMoloniDocument;

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
                Log::write(__("Automatically generating the order document", 'moloni_es') . ': ' . $orderId);

                $service = new CreateMoloniDocument($orderId);
                $orderName = $service->getOrderNumber() ?? '';

                try {
                    $service->run();

                    $this->throwMessages($service);

                    Log::write(__("Order document created successfully", 'moloni_es') . ': ' . $orderId);
                } catch (Warning $e) {
                    $this->sendWarningEmail($orderName);

                    Notice::addmessagecustom(htmlentities($e->getError()));
                    Log::write(__("There was an error generating the document: ", 'moloni_es') . strip_tags($e->getDecodedMessage()));
                } catch (Error $e) {
                    $this->sendErrorEmail($orderName);

                    Notice::addmessagecustom(htmlentities($e->getError()));
                    Log::write(__("There was an error generating the document: ", 'moloni_es') . strip_tags($e->getDecodedMessage()));
                }
            }
        } catch (Exception $ex) {
            Log::write(__("Fatal error: ", 'moloni_es') . $ex->getMessage());
        }
    }

    //          Privates          //

    private function sendWarningEmail(string $orderName): void
    {
        if (defined('ALERT_EMAIL') && !empty(ALERT_EMAIL)) {
            new DocumentWarning(ALERT_EMAIL, $orderName);
        }
    }

    private function sendErrorEmail(string $orderName): void
    {
        if (defined('ALERT_EMAIL') && !empty(ALERT_EMAIL)) {
            new DocumentFailed(ALERT_EMAIL, $orderName);
        }
    }

    private function throwMessages(CreateMoloniDocument $service): void
    {
        if ($service->getDocumentId() > 0 && is_admin()) {
            $adminUrl = esc_url(admin_url('admin.php?page=molonies&action=getInvoice&id=' . $service->getDocumentId()));
            $viewUrl = ' <a href="' . $adminUrl . '" target="_BLANK">' . __('View document', 'moloni_es') . '</a>';

            add_settings_error('molonies', 'moloni-document-created-success', __('Document was created!', 'moloni_es') . $viewUrl, 'updated');
        }
    }
}
