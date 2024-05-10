<?php

namespace MoloniES\Hooks;

use MoloniES\API\Documents;
use WC_Order;
use MoloniES\Start;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\DocumentTypes;
use MoloniES\Enums\DocumentStatus;
use MoloniES\Helpers\MoloniOrder;
use MoloniES\Exceptions\APIExeption;

class OrderDetails
{
    public $order;

    public $documents = [];

    public $htmlToRender = '';

    public function __construct()
    {
        add_action('woocommerce_order_details_after_customer_details', [$this, 'orderDetailsAfterCustomerDetails']);
    }

    public function orderDetailsAfterCustomerDetails(WC_Order $order)
    {
        $this->order = $order;

        if (!Start::login(true)) {
            return;
        }

        if (!defined('MOLONI_SHOW_DOWNLOAD_MY_ACCOUNT_ORDER_VIEW') || (int)MOLONI_SHOW_DOWNLOAD_MY_ACCOUNT_ORDER_VIEW === Boolean::NO) {
            return;
        }

        $this->loadDocuments();

        if (empty($this->documents)) {
            return;
        }

        $this->getHtmlToRender();

        apply_filters('moloni_es_before_order_details_render', $this);

        if (!empty($this->htmlToRender)) {
            echo $this->htmlToRender;
        }
    }

    private function loadDocuments(): void
    {
        $documentIds = [];

        $lastCreatedDocument = MoloniOrder::getLastCreatedDocument($this->order);

        if (!empty($lastCreatedDocument)) {
            $documentIds[] = $lastCreatedDocument;
        }

        $allCreatedCreditNotes = MoloniOrder::getAllCreatedCreditNotes($this->order);

        if (!empty($allCreatedCreditNotes)) {
            $documentIds = array_merge($documentIds, $allCreatedCreditNotes);
        }

        if (empty($documentIds)) {
            return;
        }

        foreach ($documentIds as $documentId) {
            $documentData = $this->getDocumentData($documentId);

            if (empty($documentData) || $documentData['status'] !== DocumentStatus::CLOSED) {
                continue;
            }

            $documentPdfLink = $this->getDocumentUrl($documentId);

            if (empty($documentPdfLink)) {
                continue;
            }

            $documentTypeName = DocumentTypes::getDocumentTypeName($documentData['documentType']['apiCode']);

            if (empty($documentTypeName)) {
                continue;
            }

            $this->documents[] = [
                'label' => $documentTypeName,
                'href' => $documentPdfLink,
                'data' => $documentData
            ];
        }
    }

    private function getDocumentUrl(int $documentId): string
    {
        try {
            // todo: find a way to create document URL

            if (isset($result['url'])) {
                return $result['url'];
            }
        } catch (APIExeption $e) {
        }

        return '';
    }

    private function getDocumentData(int $documentId): array
    {
        try {
            $variables = [
                'documentId' => $documentId
            ];

            $document = Documents::queryDocument($variables);

            if (isset($document['data']['document']['data']['documentId'])) {
                return $document['data']['document']['data'];
            }
        } catch (APIExeption $e) {
        }

        return [];
    }

    private function getHtmlToRender(): void
    {
        ob_start();

        ?>
        <section id="invoice_document">
            <h2>
                <?= __('Billing document', 'moloni_es') ?>
            </h2>
            <ul>
                <?php foreach ($this->documents as $document) : ?>
                    <li>
                        <a href="<?= $document['href'] ?>" target="_blank">
                            <?= $document['label'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php

        $this->htmlToRender = ob_get_clean();
    }
}
