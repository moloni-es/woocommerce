<?php

namespace MoloniES\Services\Documents;

use MoloniES\API\Documents\BillsOfLading;
use MoloniES\API\Documents\Estimate;
use MoloniES\API\Documents\Invoice;
use MoloniES\API\Documents\ProFormaInvoice;
use MoloniES\API\Documents\PurchaseOrder;
use MoloniES\API\Documents\Receipt;
use MoloniES\API\Documents\SimplifiedInvoice;
use MoloniES\Enums\DocumentTypes;
use MoloniES\Exceptions\APIExeption;

class CreateDocumentPDF
{
    private $documentId;
    private $documentType;

    /**
     * Construct
     *
     * @param int $documentId
     * @param string $documentType
     */
    public function __construct(int $documentId, string $documentType)
    {
        $this->documentId = $documentId;
        $this->documentType = $documentType;

        try {
            $this->run();
        } catch (APIExeption $e) {}
    }

    /**
     * Service runner
     *
     * @throws APIExeption
     */
    private function run(): void
    {
        $variables = [
            'documentId' => $this->documentId,
        ];

        switch ($this->documentType) {
            case DocumentTypes::INVOICE:
                Invoice::mutationInvoiceGetPDF($variables);
                break;
            case  DocumentTypes::RECEIPT:
                Receipt::mutationReceiptGetPDF($variables);
                break;
            case  DocumentTypes::ESTIMATE:
                Estimate::mutationEstimateGetPDF($variables);
                break;
            case  DocumentTypes::PURCHASE_ORDER:
                PurchaseOrder::mutationPurchaseOrderGetPDF($variables);
                break;
            case  DocumentTypes::PRO_FORMA_INVOICE:
                ProFormaInvoice::mutationProFormaInvoiceGetPDF($variables);
                break;
            case  DocumentTypes::SIMPLIFIED_INVOICE:
                SimplifiedInvoice::mutationSimplifiedInvoiceGetPDF($variables);
                break;
            case  DocumentTypes::BILLS_OF_LADING:
                BillsOfLading::mutationBillsOfLadingGetPDF($variables);
                break;
        }
    }
}
