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

class SendDocumentMail
{
    private $name;
    private $email;
    private $documentId;
    private $documentType;

    /**
     * Construct
     *
     * @param int $documentId
     * @param string $documentType
     * @param string $name
     * @param string $email
     */
    public function __construct(int $documentId, string $documentType, string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
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
            'documents' => [
                $this->documentId
            ],
            'mailData' => [
                'to' => [
                    'name' => $this->name,
                    'email' => $this->email
                ],
                'message' => '',
                'attachment' => true
            ]
        ];

        switch ($this->documentType) {
            case DocumentTypes::INVOICE:
                Invoice::mutationInvoiceSendMail($variables);
                break;
            case DocumentTypes::RECEIPT:
                Receipt::mutationReceiptSendMail($variables);
                break;
            case DocumentTypes::ESTIMATE:
                Estimate::mutationEstimateSendMail($variables);
                break;
            case DocumentTypes::PURCHASE_ORDER:
                PurchaseOrder::mutationPurchaseOrderSendMail($variables);
                break;
            case DocumentTypes::PRO_FORMA_INVOICE:
                ProFormaInvoice::mutationProFormaInvoiceSendMail($variables);
                break;
            case DocumentTypes::SIMPLIFIED_INVOICE:
                SimplifiedInvoice::mutationSimplifiedInvoiceSendMail($variables);
                break;
            case DocumentTypes::BILLS_OF_LADING:
                BillsOfLading::mutationBillsOfLadingSendMail($variables);
                break;
        }
    }
}
