<?php

namespace MoloniES\Hooks;

use Exception;
use MoloniES\Enums\AutomaticDocumentsStatus;
use MoloniES\Enums\Boolean;
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

        add_action('woocommerce_order_status_completed', [$this, 'documentCreateComplete']);
        add_action('woocommerce_order_status_processing', [$this, 'documentCreateProcessing']);
    }

    public function documentCreateComplete($orderId)
    {
        try {
            if ($this->canCreateCompleteDocument()) {
                $service = new CreateMoloniDocument($orderId);
                $orderName = $service->getOrderNumber() ?? '';

                Log::write(strtr(
                    __("Automatically generating order document in status '{0}' ({1})", 'moloni_es'),
                    [
                        '{0}' => __('Complete', 'moloni_es'),
                        '{1}' => $orderName
                    ]
                ));

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

    public function documentCreateProcessing($orderId)
    {
        try {
            if ($this->canCreateProcessingDocument()) {
                $service = new CreateMoloniDocument($orderId);
                $orderName = $service->getOrderNumber() ?? '';

                Log::write(strtr(
                    __("Automatically generating order document in status '{0}' ({1})", 'moloni_es'),
                    [
                        '{0}' => __('Processing', 'moloni_es'),
                        '{1}' => $orderName
                    ]
                ));

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

    /**
     * Verify if it can be created
     *
     * @throws Error
     */
    private function canCreateCompleteDocument(): bool
    {
        if (!Start::login(true) || !defined("INVOICE_AUTO") || (int)INVOICE_AUTO === Boolean::NO) {
            return false;
        }

        if (!defined('INVOICE_AUTO_STATUS')) {
            return true;
        }

        return INVOICE_AUTO_STATUS === AutomaticDocumentsStatus::COMPLETED;
    }

    /**
     * Verify if it can be created
     *
     * @throws Error
     */
    private function canCreateProcessingDocument(): bool
    {
        return Start::login(true)
            && defined("INVOICE_AUTO")
            && (int)INVOICE_AUTO === Boolean::YES
            && defined('INVOICE_AUTO_STATUS')
            && INVOICE_AUTO_STATUS === AutomaticDocumentsStatus::PROCESSING;
    }

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
