<?php

namespace MoloniES\Services\Documents;

use MoloniES\Exceptions\Error;
use MoloniES\Enums\DocumentTypes;
use MoloniES\API\Documents;
use MoloniES\API\Documents\Invoice;
use MoloniES\API\Documents\Receipt;
use MoloniES\API\Documents\Estimate;
use MoloniES\API\Documents\PurchaseOrder;
use MoloniES\API\Documents\BillsOfLading;
use MoloniES\API\Documents\ProFormaInvoice;
use MoloniES\API\Documents\SimplifiedInvoice;

class OpenDocument
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
        } catch (Error $e) {}
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
            return;
        }

        $invoice = $invoice['data']['document']['data'];

        if ((int)$invoice['status'] === 1) {
            unset($variables['companyId']);

            $mutation = [];
            $keyString = '';

            switch ($invoice['documentType']['apiCode']) {
                case DocumentTypes::INVOICE:
                    $mutation = Invoice::queryInvoiceGetPDFToken($variables);
                    $keyString= 'invoiceGetPDFToken';
                    break;
                case DocumentTypes::RECEIPT:
                    $mutation = Receipt::queryReceiptGetPDFToken($variables);
                    $keyString= 'receiptGetPDFToken';
                    break;
                case DocumentTypes::ESTIMATE:
                    $mutation = Estimate::queryEstimateGetPDFToken($variables);
                    $keyString= 'estimateGetPDFToken';
                    break;
                case DocumentTypes::PURCHASE_ORDER:
                    $mutation = PurchaseOrder::queryPurchaseOrderGetPDFToken($variables);
                    $keyString= 'purchaseOrderGetPDFToken';
                    break;
                case DocumentTypes::PRO_FORMA_INVOICE:
                    $mutation = ProFormaInvoice::queryProFormaInvoiceGetPDFToken($variables);
                    $keyString= 'proFormaInvoiceGetPDFToken';
                    break;
                case DocumentTypes::SIMPLIFIED_INVOICE:
                    $mutation = SimplifiedInvoice::querySimplifiedInvoiceGetPDFToken($variables);
                    $keyString= 'simplifiedInvoiceGetPDFToken';
                    break;
                case DocumentTypes::BILLS_OF_LADING:
                    $mutation = BillsOfLading::queryBillsOfLadingGetPDFToken($variables);
                    $keyString= 'billsOfLadingGetPDFToken';
                    break;
            }

            $result = $mutation['data'][$keyString]['data'];

            header('Location: https://mediaapi.moloni.org' . $result['path'] . '?jwt=' . $result['token']);
        } else {
            header('Location: https://ac.moloni.es/' . $invoice['company']['slug'] . '/' . $invoice['documentType']['apiCodePlural'] . '/view/' . $invoice['documentId']);
        }

        exit;
    }
}