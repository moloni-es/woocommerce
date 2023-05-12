<?php

namespace MoloniES\Services\Documents;

use MoloniES\Exceptions\Error;
use MoloniES\Enums\DocumentTypes;
use MoloniES\API\Documents;
use MoloniES\API\Documents\BillsOfLading;
use MoloniES\API\Documents\Estimate;
use MoloniES\API\Documents\Invoice;
use MoloniES\API\Documents\ProFormaInvoice;
use MoloniES\API\Documents\PurchaseOrder;
use MoloniES\API\Documents\Receipt;
use MoloniES\API\Documents\SimplifiedInvoice;

class DownloadDocumentPDF
{
    private $documentId;

    /**
     * Construct
     *
     * @param $documentId
     */
    public function __construct($documentId)
    {
        $this->documentId = $documentId;

        try {
            $this->run();
        } catch (Error $e) {
            $this->showError(__('Unexpected error', 'moloni_es'));
        }
    }

    /**
     * Service runner
     *
     * @throws Error
     */
    private function run(): void
    {
        $variables = [
            'documentId' => $this->documentId
        ];

        $invoice = Documents::queryDocument($variables);

        if (isset($invoice['errors']) || !isset($invoice['data']['document']['data']['documentId'])) {
            $this->showError(__('Document not found', 'moloni_es'));

            return;
        }

        $invoice = $invoice['data']['document']['data'];

        if (empty($invoice['pdfExport']) || $invoice['pdfExport'] === 'null') {
            new CreateDocumentPDF($this->documentId, $invoice['documentType']['apiCode']);
        }

        $mutation = [];
        $keyString = '';

        switch ($invoice['documentType']['apiCode']) {
            case DocumentTypes::INVOICE:
                $mutation = Invoice::queryInvoiceGetPDFToken($variables);
                $keyString = 'invoiceGetPDFToken';
                break;
            case DocumentTypes::RECEIPT:
                $mutation = Receipt::queryReceiptGetPDFToken($variables);
                $keyString = 'receiptGetPDFToken';
                break;
            case DocumentTypes::ESTIMATE:
                $mutation = Estimate::queryEstimateGetPDFToken($variables);
                $keyString = 'estimateGetPDFToken';
                break;
            case DocumentTypes::PURCHASE_ORDER:
                $mutation = PurchaseOrder::queryPurchaseOrderGetPDFToken($variables);
                $keyString = 'purchaseOrderGetPDFToken';
                break;
            case DocumentTypes::PRO_FORMA_INVOICE:
                $mutation = ProFormaInvoice::queryProFormaInvoiceGetPDFToken($variables);
                $keyString = 'proFormaInvoiceGetPDFToken';
                break;
            case DocumentTypes::SIMPLIFIED_INVOICE:
                $mutation = SimplifiedInvoice::querySimplifiedInvoiceGetPDFToken($variables);
                $keyString = 'simplifiedInvoiceGetPDFToken';
                break;
            case DocumentTypes::BILLS_OF_LADING:
                $mutation = BillsOfLading::queryBillsOfLadingGetPDFToken($variables);
                $keyString = 'billsOfLadingGetPDFToken';
                break;
        }

        $result = $mutation['data'][$keyString]['data'] ?? [];

        if (empty($result)) {
            $this->showError(__('Error getting document', 'moloni_es'));

            return;
        }

        header('Location: https://mediaapi.moloni.org' . $result['path'] . '?jwt=' . $result['token']);
    }

    private function showError($message): void
    {
        echo "<script>";
        echo "  alert('" . $message . "');";
        echo "  window.close();";
        echo "</script>";
    }
}
