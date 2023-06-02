<?php

namespace MoloniES\Hooks;

use Exception;
use MoloniES\Enums\AutomaticDocumentsStatus;
use MoloniES\Enums\Boolean;
use MoloniES\Exceptions\DocumentError;
use MoloniES\Exceptions\DocumentWarning;
use MoloniES\Notice;
use MoloniES\Plugin;
use MoloniES\Services\Mails\DocumentFailed;
use MoloniES\Services\Orders\CreateMoloniDocument;
use MoloniES\Start;
use MoloniES\Storage;

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
        if ($this->canCreateCompleteDocument()) {
            $service = new CreateMoloniDocument($orderId);
            $orderName = $service->getOrderNumber() ?? '';

            Storage::$LOGGER->info(sprintf(
                __("Automatically generating order document in status '%s' (%s)", 'moloni_es'),
                __('Complete', 'moloni_es'),
                $orderName
            ));

            try {
                $service->run();

                $this->throwMessages($service);
            } catch (DocumentWarning $e) {
                $this->sendWarningEmail($orderName);

                $message = sprintf(__('There was an warning when generating the document (%s)'), $orderName);
                $message .= ' ';
                $message .= $e->getMessage();

                Notice::addmessagecustom(htmlentities($e->getError()));

                Storage::$LOGGER->alert(
                    $message,
                    [
                        'message' => $e->getMessage(),
                        'request' => $e->getData()
                    ]
                );
            } catch (DocumentError $e) {
                $this->sendErrorEmail($orderName);

                $message = sprintf(__('There was an error when generating the document (%s)'), $orderName);
                $message .= ' ';
                $message .= $e->getMessage();

                Notice::addmessagecustom(htmlentities($e->getError()));

                Storage::$LOGGER->error(
                    $message,
                    [
                        'message' => $e->getMessage(),
                        'request' => $e->getData()
                    ]
                );
            } catch (Exception $ex) {
                Storage::$LOGGER->critical(__("Fatal error", 'moloni_es'), [
                    'action' => 'automatic:document:create:complete',
                    'exception' => $ex->getMessage()
                ]);
            }
        }
    }

    public function documentCreateProcessing($orderId)
    {
        if ($this->canCreateProcessingDocument()) {
            $service = new CreateMoloniDocument($orderId);
            $orderName = $service->getOrderNumber() ?? '';

            Storage::$LOGGER->info(sprintf(
                __("Automatically generating order document in status '%s' (%s)", 'moloni_es'),
                __('Processing', 'moloni_es'),
                $orderName
            ));

            try {
                $service->run();

                $this->throwMessages($service);
            } catch (DocumentWarning $e) {
                $this->sendWarningEmail($orderName);

                $message = sprintf(__('There was an warning when generating the document (%s)'), $orderName);
                $message .= ' ';
                $message .= $e->getMessage();

                Notice::addmessagecustom(htmlentities($e->getError()));

                Storage::$LOGGER->alert(
                    $message,
                    [
                        'message' => $e->getMessage(),
                        'request' => $e->getData()
                    ]
                );
            } catch (DocumentError $e) {
                $this->sendErrorEmail($orderName);

                $message = sprintf(__('There was an error when generating the document (%s)'), $orderName);
                $message .= ' ';
                $message .= $e->getMessage();

                Notice::addmessagecustom(htmlentities($e->getError()));

                Storage::$LOGGER->error(
                    $message,
                    [
                        'message' => $e->getMessage(),
                        'request' => $e->getData()
                    ]
                );
            } catch (Exception $ex) {
                Storage::$LOGGER->critical(__("Fatal error", 'moloni_es'), [
                    'action' => 'automatic:document:create:processing',
                    'exception' => $ex->getMessage()
                ]);
            }
        }
    }

    //          Privates          //

    /**
     * Verify if it can be created
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
