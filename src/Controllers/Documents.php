<?php

namespace MoloniES\Controllers;

use MoloniES\API\Documents\BillsOfLading;
use MoloniES\API\Documents\Estimate;
use MoloniES\API\Documents\Invoice;
use MoloniES\API\Documents\ProFormaInvoice;
use MoloniES\API\Documents\PurchaseOrder;
use MoloniES\API\Documents\Receipt;
use MoloniES\API\Documents\SimplifiedInvoice;
use MoloniES\Curl;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\Countries;
use MoloniES\Enums\DocumentStatus;
use MoloniES\Enums\DocumentTypes;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\DocumentError;
use MoloniES\Exceptions\DocumentWarning;
use MoloniES\Services\Documents\CreateDocumentPDF;
use MoloniES\Services\Documents\SendDocumentMail;
use MoloniES\Storage;
use MoloniES\Tools;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;

/**
 * Class Documents
 * Used to create and/or update a Moloni Document
 * @package Moloni\Controllers
 */
class Documents
{
    /**
     * Field used in filter to cancel document creation
     *
     * @var bool
     */
    public $stopProcess = false;

    /** @var array */
    private $company;

    /** @var array */
    private $fiscalZone;

    /**
     * Related documents
     *
     * @var array
     */
    private $relatedDocuments = [];

    /** @var WC_Order */
    private $order;

    /**
     * @var array
     */
    private $document = [];

    /** @var int */
    private $documentId = 0;

    /**
     * Moloni document total
     *
     * @var float
     */
    private $documentTotal = 0.0;

    /**
     * Moloni document exchage total total
     *
     * @var float
     */
    private $documentExchageTotal = 0.0;


    /** @var int */
    private $customerId = 0;

    /** @var int */
    private $documentSetId;

    /** @var string */
    private $ourReference = '';

    /** @var string */
    private $yourReference = '';

    /** @var string in Y-m-d */
    private $date;

    /** @var string in Y-m-d */
    private $expirationDate;

    // Delivery parameters being used if the option is set
    private $deliveryLoadDate;
    private $deliveryMethodId = 0;

    private $deliveryLoadAddress = '';
    private $deliveryLoadCity = '';
    private $deliveryLoadZipCode = '';
    private $deliveryLoadCountryId = '';

    private $deliveryUnloadAddress = '';
    private $deliveryUnloadCity = '';
    private $deliveryUnloadZipCode = '';
    private $deliveryUnloadCountryId = '';
    private $notes = '';

    private $products = [];
    private $payments = [];

    private $documentType = '';
    private $documentTypeName = '';
    private $documentStatus = 0;

    private $useShipping = 0;
    private $sendEmail = 0;

    private $currencyExchangeId = 0;
    private $currencyExchangeExchange = 0;

    /**
     * Documents constructor.
     *
     * @param WC_Order $order
     * @param array $company
     *
     * @throws DocumentError
     */
    public function __construct(WC_Order $order, array $company)
    {
        $this->order = $order;
        $this->company = $company;

        $this->init();
    }

    /**
     * Resets some values after cloning
     *
     * @return void
     */
    public function __clone()
    {
        $this->document = [];
        $this->documentId = 0;
        $this->documentTotal = 0;
        $this->documentExchageTotal = 0;

        $this->currencyExchangeId = 0;
        $this->currencyExchangeExchange = 0;

        $this->relatedDocuments = [];
    }

    /**
     * Relate a document wiht the current one
     *
     * @param int $documentId Document id to associate
     * @param float $value Total value to associate
     * @param array $products Document products
     *
     * @return $this
     */
    public function addRelatedDocument(int $documentId, float $value, array $products = []): Documents
    {
        $this->relatedDocuments[] = [
            'documentId' => $documentId,
            'value' => $value,
            'products' => $products
        ];

        return $this;
    }

    /**
     * Creates an document
     *
     * @return $this
     *
     * @throws DocumentWarning
     * @throws DocumentError
     */
    public function createDocument(): Documents
    {
        apply_filters('moloni_es_before_insert_document', $this);

        if ($this->stopProcess) {
            throw new DocumentError(__('Document creation stopped', 'moloni_es'));
        }

        $keyString = '';
        $mutation = [];
        $props = $this->mapPropsToValues();

        try {
            switch ($this->documentType) {
                case DocumentTypes::INVOICE:
                    $mutation = Invoice::mutationInvoiceCreate($props);
                    $keyString = 'invoiceCreate';
                    break;
                case DocumentTypes::RECEIPT:
                    $mutation = Receipt::mutationReceiptCreate($props);
                    $keyString = 'receiptCreate';
                    break;
                case DocumentTypes::ESTIMATE:
                    $mutation = Estimate::mutationEstimateCreate($props);
                    $keyString = 'estimateCreate';
                    break;
                case DocumentTypes::PURCHASE_ORDER:
                    $mutation = PurchaseOrder::mutationPurchaseOrderCreate($props);
                    $keyString = 'purchaseOrderCreate';
                    break;
                case DocumentTypes::PRO_FORMA_INVOICE:
                    $mutation = ProFormaInvoice::mutationProFormaInvoiceCreate($props);
                    $keyString = 'proFormaInvoiceCreate';
                    break;
                case DocumentTypes::SIMPLIFIED_INVOICE:
                    $mutation = SimplifiedInvoice::mutationSimplifiedInvoiceCreate($props);
                    $keyString = 'simplifiedInvoiceCreate';
                    break;
                case DocumentTypes::BILLS_OF_LADING:
                    $mutation = BillsOfLading::mutationBillsOfLadingCreate($props);
                    $keyString = 'billsOfLadingCreate';
                    break;
            }
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error creating document', 'moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData(),
                ]
            );
        }

        $this->document = $mutation['data'][$keyString]['data'] ?? [];

        if (!isset($this->document['documentId'])) {
            throw new DocumentError(
                __('Error creating document', 'moloni_es'),
                Curl::getLog()
            );
        }

        $this->documentId = (int)$this->document['documentId'];
        $this->documentTotal = (float)$this->document['totalValue'];
        $this->documentExchageTotal = $this->document['currencyExchangeTotalValue'] > 0 ?
            (float)$this->document['currencyExchangeTotalValue'] :
            $this->documentTotal;

        $this->saveRecord();

        apply_filters('moloni_es_after_insert_document', $this);

        if ($this->shouldCloseDocument()) {
            $this->closeDocument();
        } else {
            $note = __('Document inserted as a draft in Moloni', 'moloni_es');
            $note .= " (" . $this->documentTypeName . ")";

            $this->order->add_order_note($note);
        }

        $this->saveLog();

        return $this;
    }

    /**
     * Close a document based on its id
     *
     * @throws DocumentWarning
     * @throws DocumentError
     */
    public function closeDocument()
    {
        $orderTotal = ((float)$this->order->get_total() - (float)$this->order->get_total_refunded());
        $documentTotal = $this->getDocumentExchageTotal();

        if (abs($orderTotal - $documentTotal) > 0.01) {
            $note = __('Document inserted as a draft in Moloni', 'moloni_es');
            $note .= " (" . $this->documentTypeName . ")";

            $this->order->add_order_note($note);

            $viewUrl = admin_url('admin.php?page=molonies&action=getInvoice&id=' . $this->documentId);

            throw new DocumentWarning(
                __('The document has been inserted but the totals do not match. ', 'moloni_es') .
                '<a href="' . esc_url($viewUrl) . '" target="_BLANK">' . __('See document', 'moloni_es') . '</a>'
            );
        }

        $keyString = '';
        $mutation = [];

        $variables = [
            'data' => [
                'documentId' => $this->documentId,
                'status' => DocumentStatus::CLOSED
            ]
        ];

        try {
            switch ($this->documentType) {
                case DocumentTypes::INVOICE:
                    $mutation = Invoice::mutationInvoiceUpdate($variables);

                    $keyString = 'invoiceUpdate';
                    break;
                case DocumentTypes::RECEIPT:
                    $mutation = Receipt::mutationReceiptUpdate($variables);
                    $keyString = 'receiptUpdate';
                    break;
                case DocumentTypes::ESTIMATE:
                    $mutation = Estimate::mutationEstimateUpdate($variables);
                    $keyString = 'estimateUpdate';
                    break;
                case DocumentTypes::PURCHASE_ORDER:
                    $mutation = PurchaseOrder::mutationPurchaseOrderUpdate($variables);
                    $keyString = 'purchaseOrderUpdate';
                    break;
                case DocumentTypes::PRO_FORMA_INVOICE:
                    $mutation = ProFormaInvoice::mutationProFormaInvoiceUpdate($variables);
                    $keyString = 'proFormaInvoiceUpdate';
                    break;
                case DocumentTypes::SIMPLIFIED_INVOICE:
                    $mutation = SimplifiedInvoice::mutationSimplifiedInvoiceUpdate($variables);
                    $keyString = 'simplifiedInvoiceUpdate';
                    break;
                case DocumentTypes::BILLS_OF_LADING:
                    $mutation = BillsOfLading::mutationBillsOfLadingUpdate($variables);
                    $keyString = 'billsOfLadingUpdate';
                    break;
            }
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error closing document', 'moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData(),
                ]
            );
        }

        if (isset($mutation['errors']) || !isset($mutation['data'][$keyString]['data'])) {
            throw new DocumentError(
                __('Error closing document', 'moloni_es'),
                [
                    'variables' => $variables,
                    'mutation' => $mutation,
                ]
            );
        }

        // Send email to the client
        if ($this->shouldSendEmail()) {
            new CreateDocumentPDF(
                $this->documentId,
                $this->documentType
            );
            new SendDocumentMail(
                $this->documentId,
                $this->documentType,
                $this->order->get_billing_first_name() . ' ' . $this->order->get_billing_last_name(),
                $this->order->get_billing_email()
            );

            $this->order->add_order_note(__('Document sent by email to the customer', 'moloni_es'));
        }

        apply_filters('moloni_es_after_close_document', $this);

        $note = __('Document inserted in Moloni', 'moloni_es');
        $note .= " (" . $this->documentTypeName . ")";

        $this->order->add_order_note($note);
    }

    //          PRIVATES          //

    /**
     * Initialize document values
     *
     * @return void
     *
     * @throws DocumentError
     */
    private function init(): void
    {
        apply_filters('moloni_es_before_start_document', $this);

        $this
            ->setYourReference()
            ->setOurReference()
            ->setDates()
            ->setDocumentStatus()
            ->setCustomer()
            ->setDocumentSetId()
            ->setSendEmail()
            ->setFiscalZone()
            ->setProducts()
            ->setShipping()
            ->setFees()
            ->setExchangeRate()
            ->setShippingInformation()
            ->setDelivery()
            ->setPaymentMethod()
            ->setNotes();
    }

    /**
     * Save document log
     *
     * @return void
     */
    private function saveLog(): void
    {
        $message = sprintf(
            __('%s was created with success (%s)', 'moloni_es'),
            $this->documentTypeName,
            $this->order->get_order_number()
        );

        Storage::$LOGGER->info($message, [
            'tag' => 'service:create:document',
            'order_id' => $this->order->get_id(),
            'document_id' => $this->documentId,
            'document_status' => $this->documentStatus,
        ]);
    }

    /**
     * Save document id on order meta
     *
     * @return void
     */
    private function saveRecord(): void
    {
        $this->order->add_meta_data('_molonies_sent', $this->documentId);
        $this->order->save();
    }

    /**
     * Map this object properties to an array to insert/update a moloni document
     *
     * @return array
     */
    private function mapPropsToValues(): array
    {
        $variables = [
            'fiscalZone' => $this->fiscalZone['code'],
            'customerId' => $this->customerId,
            'documentSetId' => $this->documentSetId,
            'ourReference' => $this->ourReference,
            'yourReference' => $this->yourReference,
            'expirationDate' => $this->expirationDate,
            'date' => $this->date,
            'notes' => $this->notes,
            'status' => DocumentStatus::DRAFT,
        ];

        if (!empty($this->products) && $this->shouldAddProducts()) {
            $variables['products'] = $this->products;
        }

        if (!empty($this->payments) && $this->shouldAddPayment()) {
            $variables['payments'] = $this->payments;
        }

        if (!empty($this->deliveryMethodId) && $this->shouldAddShippingInformation()) {
            $variables['deliveryMethodId'] = $this->deliveryMethodId;
            $variables['deliveryLoadDate'] = $this->deliveryLoadDate;
            $variables['deliveryLoadAddress'] = $this->deliveryLoadAddress;
            $variables['deliveryLoadCity'] = $this->deliveryLoadCity;
            $variables['deliveryLoadZipCode'] = $this->deliveryLoadZipCode;
            $variables['deliveryLoadCountryId'] = (int)$this->deliveryLoadCountryId;
            $variables['deliveryUnloadAddress'] = $this->deliveryUnloadAddress;
            $variables['deliveryUnloadCity'] = $this->deliveryUnloadCity;
            $variables['deliveryUnloadZipCode'] = $this->deliveryUnloadZipCode;
            $variables['deliveryUnloadCountryId'] = (int)$this->deliveryUnloadCountryId;
        }

        if (!empty($this->currencyExchangeId)) {
            $variables['currencyExchangeId'] = $this->currencyExchangeId;
            $variables['currencyExchangeExchange'] = $this->currencyExchangeExchange;
        }

        if (!empty($this->relatedDocuments)) {
            $relatedWithTotal = 0.0;
            $variables['relatedWith'] = [];

            foreach ($this->relatedDocuments as $related) {
                $relatedWithTotal += $related['value'];

                $variables['relatedWith'][] = [
                    'relatedDocumentId' => $related['documentId'],
                    'value' => $related['value'],
                ];

                /** Associate products from both documents */
                if (!empty($related['products']) && !empty($variables['products'])) {
                    /**
                     * If multiple documents are associated, the need a global product counter
                     * Starts in -1 because the first thing we do is to increment its value
                     */
                    $currentProductIndex = -1;

                    /**
                     * Associate products from both documents
                     * We assume that the order of the documents is the same (beware if tring to do custom stuff)
                     */
                    foreach ($related['products'] as $associatedProduct) {
                        $currentProductIndex++;

                        /** To avoid errors, check lenght */
                        if (!isset($variables['products'][$currentProductIndex])) {
                            continue;
                        }

                        /** Ids have to match */
                        if ((int)$variables['products'][$currentProductIndex]['productId'] !== (int)$associatedProduct['productId']) {
                            continue;
                        }

                        $variables['products'][$currentProductIndex]['relatedDocumentId'] = (int)$related['documentId'];
                        $variables['products'][$currentProductIndex]['relatedDocumentProductId'] = (int)$associatedProduct['documentProductId'];
                    }
                }
            }

            /** Just Receipts things */
            if ($this->documentType === DocumentTypes::RECEIPT) {
                unset(
                    $variables['expirationDate'],
                    $variables['ourReference'],
                    $variables['yourReference']
                );

                $variables['totalValue'] = $relatedWithTotal;
            }
        }

        return ['data' => $variables];
    }

    //          GETS          //

    /**
     * Get document id
     *
     * @return int
     */
    public function getDocumentId(): int
    {
        return $this->documentId ?? 0;
    }

    /**
     * Get document total
     *
     * @return float|int
     */
    public function getDocumentTotal()
    {
        return $this->documentTotal ?? 0;
    }

    /**
     * Get document exchange total
     *
     * @return float|int
     */
    public function getDocumentExchageTotal()
    {
        return $this->documentExchageTotal ?? 0;
    }

    /**
     * Get created document products
     *
     * @return array
     */
    public function getDocumentProducts(): array
    {
        return $this->document['products'] ?? [];
    }

    //          SETS          //

    /**
     * Set document reference
     *
     * @return $this
     */
    public function setYourReference(): Documents
    {
        $this->yourReference = '#' . $this->order->get_order_number();

        return $this;
    }

    /**
     * Set document reference
     *
     * @return $this
     */
    public function setOurReference(): Documents
    {
        $this->ourReference = '#' . $this->order->get_order_number();

        return $this;
    }

    /**
     * Set dates
     *
     * @return $this
     */
    public function setDates(): Documents
    {
        $this->date = date('Y-m-d H:i:s');
        $this->expirationDate = date('Y-m-d H:i:s');

        return $this;
    }

    /**
     * Set document status
     *
     * @param $documentStatus
     *
     * @return $this
     */
    public function setDocumentStatus($documentStatus = null): Documents
    {
        switch (true) {
            case $documentStatus !== null:
                $this->documentStatus = (int)$documentStatus;

                break;
            case defined('DOCUMENT_STATUS'):
                $this->documentStatus = (int)DOCUMENT_STATUS;

                break;
            default:
                $this->documentStatus = DocumentStatus::DRAFT;

                break;
        }

        return $this;
    }

    /**
     * Set costumer
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setCustomer(): Documents
    {
        $this->customerId = (new OrderCustomer($this->order))->create();

        return $this;
    }

    /**
     * Set document type
     *
     * @param $documentType
     *
     * @return $this
     */
    public function setDocumentType($documentType = null): Documents
    {
        switch (true) {
            case !empty($documentType):
                $this->documentType = $documentType;

                break;
            case defined('DOCUMENT_TYPE'):
                $this->documentType = DOCUMENT_TYPE;
                break;
            default:
                $this->documentType = '';

                break;
        }

        if (!empty($this->documentType)) {
            $this->documentTypeName = DocumentTypes::getDocumentTypeName($this->documentType);
        }

        return $this;
    }

    /**
     * Set fiscal zone
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setFiscalZone(): Documents
    {
        $fiscalZone = [];
        $addressCode = '';
        $defaultValues = [
            'code' => $this->company['fiscalZone']['fiscalZone'] ?? 'ES',
            'countryId' => $this->company['country']['countryId'] ?? Countries::SPAIN
        ];

        switch (get_option('woocommerce_tax_based_on')) {
            case 'billing':
                $addressCode = $this->order->get_billing_country();

                break;
            case 'shipping':
                $addressCode = $this->order->get_shipping_country();

                break;
            case 'base':
            default:
                $fiscalZone = $defaultValues;

                break;
        }

        if (!empty($addressCode)) {
            try {
                ['countryId' => $countryId, 'code' => $code] = Tools::getMoloniCountryByCode($addressCode);
            } catch (APIExeption $e) {
                throw new DocumentError(
                    __('Error fetching document fiscal zone', 'moloni_es'),
                    [
                        'message' => $e->getMessage(),
                        'data' => $e->getData()
                    ]
                );
            }

            $fiscalZone = [
                'code' => $code,
                'countryId' => $countryId
            ];
        }

        if (empty($fiscalZone)) {
            $fiscalZone = $defaultValues;
        }

        $this->fiscalZone = $fiscalZone;

        return $this;
    }

    /**
     * Gets document set
     *
     * @return Documents
     *
     * @throws DocumentError
     */
    public function setDocumentSetId(): Documents
    {
        $documentSetId = 0;

        if (defined('DOCUMENT_SET_ID')) {
            $documentSetId = (int)DOCUMENT_SET_ID;
        }

        if ($documentSetId === 0) {
            throw new DocumentError(__('Document set missing. Please select a document set in settings.', 'moloni_es'));
        }

        $this->documentSetId = $documentSetId;

        return $this;
    }

    /**
     * Set send by email
     *
     * @param $sendByEmail
     *
     * @return $this
     */
    public function setSendEmail($sendByEmail = null): Documents
    {
        switch (true) {
            case $sendByEmail !== null:
                $this->sendEmail = (int)$sendByEmail;

                break;
            case defined('EMAIL_SEND'):
                $this->sendEmail = (int)EMAIL_SEND;

                break;
            default:
                $this->sendEmail = 0;

                break;
        }

        return $this;
    }

    /**
     * Sets order products
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setProducts(): Documents
    {
        foreach ($this->order->get_items() as $orderProduct) {
            /** @var $orderProduct WC_Order_Item_Product */
            $newOrderProduct = new OrderProduct($orderProduct, $this->order, count($this->products), $this->fiscalZone);

            $this->products[] = $newOrderProduct->create()->mapPropsToValues();

        }

        return $this;
    }

    /**
     * Sets order shipping
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setShipping(): Documents
    {
        if ($this->order->get_shipping_method() && (float)$this->order->get_shipping_total() > 0) {
            $newOrderShipping = new OrderShipping($this->order, count($this->products), $this->fiscalZone);
            $newOrderShipping->create();

            if ($newOrderShipping->getPrice() > 0) {
                $this->products[] = $newOrderShipping->mapPropsToValues();
            }
        }

        return $this;
    }

    /**
     * Sets order fees
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setFees(): Documents
    {
        foreach ($this->order->get_fees() as $item) {
            /** @var $item WC_Order_Item_Fee */
            $feePrice = abs($item['line_total']);

            if ($feePrice > 0) {
                $newOrderFee = new OrderFees($item, count($this->products), $this->fiscalZone);
                $this->products[] = $newOrderFee->create()->mapPropsToValues();
            }
        }

        return $this;
    }

    /**
     * Gets exchange info
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setExchangeRate(): Documents
    {
        if ($this->company['currency']['iso4217'] !== $this->order->get_currency()) {

            try {
                $result = Tools::getCurrencyExchangeRate($this->company['currency']['iso4217'], $this->order->get_currency());
            } catch (APIExeption $e) {
                throw new DocumentError(
                    __('Error fetching exchange rate.', 'moloni_es'),
                    [
                        'message' => $e->getMessage(),
                        'data' => $e->getData()
                    ]
                );
            }

            $this->currencyExchangeId = (int)$result['currencyExchangeId'];
            $this->currencyExchangeExchange = (float)$result['exchange'];

            if (!empty($this->products) && is_array($this->products)) {
                foreach ($this->products as &$product) {
                    $product['price'] /= $this->currencyExchangeExchange;
                }
            }
        }

        return $this;
    }

    /**
     * Set use shipping information
     *
     * @param int|null $useShipping
     *
     * @return $this
     */
    public function setShippingInformation(?int $useShipping = null): Documents
    {
        switch (true) {
            case $useShipping !== null:
                $this->useShipping = $useShipping;

                break;
            case defined('SHIPPING_INFO'):
                $this->useShipping = (int)SHIPPING_INFO;

                break;
            default:
                $this->useShipping = 0;

                break;
        }

        return $this;
    }

    /**
     * Set the document Payment Method
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setPaymentMethod(): Documents
    {
        $paymentMethodName = $this->order->get_payment_method_title();

        if (!empty($paymentMethodName)) {
            $paymentMethod = new Payment($paymentMethodName);

            if (!$paymentMethod->loadByName()) {
                $paymentMethod->create();
            }

            if ((int)$paymentMethod->payment_method_id > 0) {
                $this->payments[] = [
                    'paymentMethodId' => (int)$paymentMethod->payment_method_id,
                    'date' => date('Y-m-d H:i:s'),
                    'paymentMethodName' => $paymentMethodName,
                    'value' => ((float)$this->order->get_total() - (float)$this->order->get_total_refunded())
                ];
            }
        }

        return $this;
    }

    /**
     * Set the document customer notes
     */
    public function setNotes(): void
    {
        $notes = $this->order->get_customer_order_notes();

        if (!empty($notes)) {
            foreach ($notes as $index => $note) {
                $this->notes .= $note->comment_content;
                if ($index !== count($notes) - 1) {
                    $this->notes .= '<br>';
                }
            }
        }
    }

    /**
     * Sets shipping info
     *
     * @return $this
     *
     * @throws DocumentError
     */
    public function setDelivery(): Documents
    {
        $shippingName = $this->order->get_shipping_method();

        if (empty($shippingName)) {
            return $this;
        }

        $this->deliveryUnloadZipCode = $this->order->get_shipping_postcode();

        $deliveryMethod = new DeliveryMethod($shippingName);

        if (!$deliveryMethod->loadByName()) {
            $deliveryMethod->create();
        }

        if (empty($deliveryMethod->delivery_method_id)) {
            $deliveryMethod->loadDefault();
        }

        $this->deliveryMethodId = $deliveryMethod->delivery_method_id;
        $this->deliveryLoadDate = date('Y-m-d H:i:s');

        $loadSetting = defined('LOAD_ADDRESS') ? (int)LOAD_ADDRESS : 0;

        if ($loadSetting === 1 &&
            defined('LOAD_ADDRESS_CUSTOM_ADDRESS') &&
            defined('LOAD_ADDRESS_CUSTOM_CITY') &&
            defined('LOAD_ADDRESS_CUSTOM_CODE') &&
            defined('LOAD_ADDRESS_CUSTOM_COUNTRY')) {
            $this->deliveryLoadAddress = LOAD_ADDRESS_CUSTOM_ADDRESS;
            $this->deliveryLoadCity = LOAD_ADDRESS_CUSTOM_CITY;
            $this->deliveryLoadZipCode = LOAD_ADDRESS_CUSTOM_CODE;
            $this->deliveryLoadCountryId = (int)LOAD_ADDRESS_CUSTOM_COUNTRY;
        } else {
            $this->deliveryLoadAddress = $this->company['address'];
            $this->deliveryLoadCity = $this->company['city'];
            $this->deliveryLoadZipCode = $this->company['zipCode'];
            $this->deliveryLoadCountryId = (int)$this->company['country']['countryId'];
        }

        $this->deliveryUnloadAddress = $this->order->get_shipping_address_1() . ' ' . $this->order->get_shipping_address_2();
        $this->deliveryUnloadCity = $this->order->get_shipping_city();

        try {
            ['countryId' => $countryId] = Tools::getMoloniCountryByCode($this->order->get_shipping_country());
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error fetching country', 'moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        }

        $this->deliveryUnloadCountryId = $countryId;

        return $this;
    }

    //          VERIFICATIONS          //

    /**
     * Checks if document should have payments
     *
     * @return bool
     */
    protected function shouldAddPayment(): bool
    {
        return DocumentTypes::hasPayments($this->documentType);
    }

    /**
     * Checks if document type can have products
     *
     * @return bool
     */
    protected function shouldAddProducts(): bool
    {
        return DocumentTypes::hasProducts($this->documentType);
    }

    /**
     * Checks if document should be closed
     *
     * @return bool
     */
    protected function shouldCloseDocument(): bool
    {
        return $this->documentStatus === DocumentStatus::CLOSED;
    }

    /**
     * Checks if document should be sent via email
     *
     * @return bool
     */
    protected function shouldSendEmail(): bool
    {
        return $this->sendEmail === Boolean::YES;
    }

    /**
     * Checks if document should have shipping information
     *
     * @return bool
     */
    protected function shouldAddShippingInformation(): bool
    {
        if (DocumentTypes::requiresDelivery($this->documentType)) {
            return true;
        }

        return $this->useShipping === Boolean::YES && DocumentTypes::hasDelivery($this->documentType);
    }
}
