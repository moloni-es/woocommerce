<?php

namespace MoloniES\Services\Orders;

use MoloniES\Exceptions\Warning;
use WC_Order;
use MoloniES\API\Companies;
use MoloniES\Exceptions\Error;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\DocumentTypes;
use MoloniES\Enums\DocumentStatus;
use MoloniES\Controllers\Documents;

class CreateMoloniDocument
{
    /**
     * Order object
     *
     * @var WC_Order
     */
    private $order;

    /**
     * Created document id
     *
     * @var int
     */
    private $documentId = 0;

    /**
     * Document type
     *
     * @var string|null
     */
    private $documentType;

    public function __construct($orderId)
    {
        $this->order = new WC_Order((int)$orderId);
        $this->documentType = isset($_GET['document_type']) ? sanitize_text_field($_GET['document_type']) : null;

        if (empty($this->documentType) && defined('DOCUMENT_TYPE')) {
            $this->documentType = DOCUMENT_TYPE;
        }
    }

    /**
     * Run service
     *
     * @throws Warning
     * @throws Error
     */
    public function run(): void
    {
        $this->checkForWarnings();

        $company = (Companies::queryCompany())['data']['company']['data'];

        if ($this->shouldCreateBillOfLading()) {
            $billOfLading = new Documents($this->order, $company);
            $billOfLading
                ->setDocumentType(DocumentTypes::BILLS_OF_LADING)
                ->setDocumentStatus(DocumentStatus::CLOSED)
                ->setSendEmail(Boolean::NO)
                ->setShippingInformation(Boolean::YES)
                ->createDocument();
        }

        if (isset($billOfLading)) {
            $builder = clone $billOfLading;

            $builder
                ->setDocumentStatus()
                ->setSendEmail()
                ->setShippingInformation()
                ->addRelatedDocument(
                    $billOfLading->getDocumentId(),
                    $billOfLading->getDocumentTotal(),
                    $billOfLading->getDocumentProducts()
                );

            unset($billOfLading);
        } else {
            $builder = new Documents($this->order, $company);
        }

        if ($this->documentType === DocumentTypes::INVOICE_AND_RECEIPT) {
            $builder
                ->setDocumentType(DocumentTypes::INVOICE)
                ->setDocumentStatus(DocumentStatus::CLOSED)
                ->setSendEmail(Boolean::NO)
                ->createDocument();

            $receipt = clone $builder;

            $receipt
                ->addRelatedDocument(
                    $builder->getDocumentId(),
                    $builder->getDocumentTotal(),
                    $builder->getDocumentProducts()
                )
                ->setDocumentType(DocumentTypes::RECEIPT)
                ->setDocumentStatus(DocumentStatus::CLOSED)
                ->setSendEmail()
                ->createDocument();
        } else {
            $builder
                ->setDocumentType($this->documentType)
                ->createDocument();
        }

        $this->documentId = $builder->getDocumentId();
    }

    //          GETS          //

    public function getDocumentId(): int
    {
        return $this->documentId ?? 0;
    }

    public function getOrderID(): int
    {
        return (int)$this->order->get_id();
    }

    public function getOrderNumber(): string
    {
        return $this->order->get_order_number() ?? '';
    }


    //          PRIVATES          //

    private function shouldCreateBillOfLading(): bool
    {
        if (defined('DOCUMENT_STATUS') && (int)DOCUMENT_STATUS === DocumentStatus::DRAFT) {
            return false;
        }

        if ($this->documentType === DocumentTypes::BILLS_OF_LADING) {
            return false;
        }

        if (defined('CREATE_BILL_OF_LADING')) {
            return (bool)CREATE_BILL_OF_LADING;
        }

        return false;
    }

    private function isReferencedInDatabase(): bool
    {
        return (bool)$this->order->get_meta('_molonies_sent');
    }

    /**
     * Checks if order already has a document associated
     *
     * @throws Error
     */
    private function checkForWarnings(): void
    {
        if ((!isset($_GET['force']) || sanitize_text_field($_GET['force']) !== 'true') && $this->isReferencedInDatabase()) {
            $forceUrl = 'admin.php?page=molonies&action=genInvoice&id=' . $this->order->get_id() . '&force=true';

            if (!empty($this->documentType)) {
                $forceUrl .= '&document_type=' . sanitize_text_field($this->documentType);
            }

            $errorMsg = sprintf(__('The order %s document was previously generated!', 'moloni_es'), $this->order->get_order_number());
            $errorMsg .= " <a href='" . esc_url($forceUrl) . "'>" . __('Generate again', 'moloni_es') . '</a>';

            throw new Error($errorMsg);
        }
    }
}