<?php

namespace MoloniES\Controllers;

use MoloniES\API\Companies;
use MoloniES\API\DeliveryMethods;
use MoloniES\API\Documents as APIDocuments;
use MoloniES\Curl;
use MoloniES\Error;
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
    /** @var bool */
    public $isHook = false;

    /** @var array */
    private $company = [];

    /** @var string */
    private $fiscalZone;

    /** @var int */
    private $orderId;

    /** @var WC_Order */
    public $order;

    /** @var bool|Error */
    private $error = false;

    /** @var int */
    public $documentId;

    /** @var int */
    private $customer_id;

    /** @var int */
    private $document_set_id;

    /** @var string */
    private $ourReference = '';

    /** @var string */
    private $yourReference = '';

    /** @var string in Y-m-d */
    private $date;

    /** @var string in Y-m-d */
    private $expiration_date;

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

    private $status = 0;

    private $products = [];
    private $payments = [];

    public $documentType;

    /** @var int */
    private $currencyExchangeId;
    private $currencyExchangeExchange;

    /**
     * Documents constructor.
     * @param int $orderId
     * @throws Error
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
        $this->order = new WC_Order((int)$orderId);

        if (!defined('DOCUMENT_TYPE')) {
            throw new Error(__('Document type not set in settings','moloni_es'));
        }

        $this->documentType = isset($_GET['document_type']) ? sanitize_text_field($_GET['document_type']) : DOCUMENT_TYPE;
    }

    /**
     * Gets the error object
     * @return bool|Error
     */
    public function getError()
    {
        return $this->error ?: false;
    }

    /**
     * Creates an document
     * @return $this
     */
    public function createDocument()
    {
        try {
            $this->company = (Companies::queryCompany(['companyId' => (int)MOLONIES_COMPANY_ID]))['data']['company']['data'];
            $this->customer_id = (new OrderCustomer($this->order))->create();
            $this->document_set_id = $this->getDocumentSetId();

            $this->date = date('Y-m-d H:i:s');
            $this->expiration_date = date('Y-m-d H:i:s');

            $this->ourReference = '#' . $this->order->get_order_number();
            $this->yourReference = '#' . $this->order->get_order_number();

            $this->checkForWarnings();

            $this
                ->setFiscalZone()
                ->setProducts()
                ->setShipping()
                ->setFees()
                ->setExchangeRate()
                ->setShippingInfo()
                ->setPaymentMethod()
                ->setNotes();

            $insertedDocument = $this->createDocumentSwitch();

            if (!isset($insertedDocument['documentId'])) {
                throw new Error(sprintf(__('Warning, there was an error inserting the document %s','moloni_es'), $this->order->get_order_number()),Curl::getLog());
            }

            $this->documentId = $insertedDocument['documentId'];
            add_post_meta($this->orderId, '_molonies_sent', $this->documentId, true);

            // If the documents is going to be inserted as closed
            if (defined('DOCUMENT_STATUS') && DOCUMENT_STATUS) {

                // Validate if the document totals match can be closed
                $orderTotal = ((float)$this->order->get_total() - (float)$this->order->get_total_refunded());
                $documentTotal = (float)$insertedDocument['currencyExchangeTotalValue'] > 0 ? (float)$insertedDocument['currencyExchangeTotalValue'] : (float)$insertedDocument['totalValue'];

                if ($orderTotal !== $documentTotal) {
                    $viewUrl = admin_url('admin.php?page=molonies&action=getInvoice&id=' . $this->documentId);
                    throw new Error(
                        __('The document has been inserted but the totals do not match. ' , 'moloni_es') .
                        '<a href="' . esc_url($viewUrl) . '" target="_BLANK">' . __('See document','moloni_es') . '</a>'
                    );
                }

                $this->closeDocument();
                $this->createPDF();

                // Send email to the client
                if (defined('EMAIL_SEND') && EMAIL_SEND) {
                    $this->order->add_order_note(__('Document sent by email to the customer','moloni_es'));

                    $this->sendEmail();
                }

                $this->order->add_order_note(__('Document inserted in Moloni','moloni_es'));
            } else {
                $this->order->add_order_note(__('Document inserted as a draft in Moloni','moloni_es'));
            }
        } catch (Error $error) {
            $this->documentId = 0;
            $this->error = $error;
        }

        return $this;
    }

    /**
     * Set fiscal zone
     *
     * @return $this
     *
     * @throws Error
     */
    public function setFiscalZone()
    {
        $fiscalZone = null;

        if (isset($_GET['fiscalZone']) && !empty($_GET['fiscalZone'])) {
            $validValue = sanitize_text_field($_GET['fiscalZone']);
        } else {
            $validValue = get_option('woocommerce_tax_based_on');
        }

        switch ($validValue) {
            case 'billing':
                $fiscalZone = $this->order->get_billing_country();

                break;
            case 'shipping':
                $fiscalZone = $this->order->get_shipping_country();

                break;
            case 'base':
                $fiscalZone = $this->company['fiscalZone']['fiscalZone'];

                break;
        }

        if (empty($fiscalZone)) {
            $fiscalZone = $this->company['fiscalZone']['fiscalZone'];
        }

        $this->fiscalZone = $fiscalZone;

        return $this;
    }

    /**
     * Sets order products
     *
     * @return $this
     *
     * @throws Error
     */
    private function setProducts()
    {
        foreach ($this->order->get_items() as $itemIndex => $orderProduct) {
            /** @var $orderProduct WC_Order_Item_Product */
            $newOrderProduct = new OrderProduct($orderProduct, $this->order, count($this->products), $this->fiscalZone);
            $this->products[] = $newOrderProduct->create()->mapPropsToValues();

        }

        return $this;
    }

    /**
     * Sets order shipping
     * @return $this
     * @throws Error
     */
    private function setShipping()
    {
        if ($this->order->get_shipping_method() && (float)$this->order->get_shipping_total() > 0) {
            $newOrderShipping = new OrderShipping($this->order, count($this->products), $this->fiscalZone);
            $this->products[] = $newOrderShipping->create()->mapPropsToValues();
        }

        return $this;
    }

    /**
     * Sets order fees
     * @return $this
     * @throws Error
     */
    private function setFees()
    {
        foreach ($this->order->get_fees() as $key => $item) {
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
     * @return $this
     * @throws Error
     */
    private function setExchangeRate()
    {
        if ($this->company['currency']['iso4217'] !== $this->order->get_currency()) {
            $result = Tools::getCurrencyExchangeRate($this->company['currency']['iso4217'], $this->order->get_currency());
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
     * Set the document Payment Method
     * @return $this
     * @throws Error
     */
    private function setPaymentMethod()
    {
        $paymentMethodName = $this->order->get_payment_method_title();

        if (!empty($paymentMethodName)) {
            $paymentMethod = new Payment($paymentMethodName);
            if (!$paymentMethod->loadByName()) {
                $paymentMethod->create();
            }

            if ((int)$paymentMethod->payment_method_id > 0) {
                $this->payments[] = [
                    'paymentMethodId' => (int) $paymentMethod->payment_method_id,
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
    private function setNotes()
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
     * @return $this
     * @throws Error
     */
    public function setShippingInfo()
    {
        if ((defined('SHIPPING_INFO') && SHIPPING_INFO) || $this->documentType === 'billsOfLading') {
            $variables = [
                'companyId' => (int) MOLONIES_COMPANY_ID,
                'options' => [
                    'filter' => [
                        'field' => 'isDefault',
                        'comparison' => 'eq',
                        'value' => '1'
                    ]
                ]
            ];
            $deliveryMethods = DeliveryMethods::queryDeliveryMethods($variables);

            if (empty($deliveryMethods)) {
                $this->deliveryMethodId = null;
            } else {
                //use the first result because can only exist one default delivery method
                $this->deliveryMethodId = $deliveryMethods[0]['deliveryMethodId'];
            }

            $this->deliveryUnloadZipCode = $this->order->get_shipping_postcode();
            if ($this->order->get_shipping_country() === 'PT') {
                $this->deliveryUnloadZipCode = Tools::zipCheck($this->deliveryUnloadZipCode);
            }

            $this->deliveryLoadDate = date('Y-m-d H:i:s');

            $this->deliveryLoadAddress = $this->company['address'];
            $this->deliveryLoadCity = $this->company['city'];
            $this->deliveryLoadZipCode = $this->company['zipCode'];
            $this->deliveryLoadCountryId = (int) $this->company['country']['countryId'];

            $this->deliveryUnloadAddress = $this->order->get_shipping_address_1() . ' ' . $this->order->get_shipping_address_2();
            $this->deliveryUnloadCity = $this->order->get_shipping_city();
            $this->deliveryUnloadCountryId = Tools::getCountryIdFromCode($this->order->get_shipping_country());
        }

        return $this;
    }

    /**
     * Gets document set
     * @return int
     * @throws Error
     */
    public function getDocumentSetId()
    {
        if (defined('DOCUMENT_SET_ID') && (int)DOCUMENT_SET_ID > 0) {
            return DOCUMENT_SET_ID;
        }

        throw new Error(__('Série de documentos em falta. <br>Por favor seleccione uma série nas opções do plugin', false));
    }

    /**
     * Checks if this document is referenced in database
     * @return bool
     */
    public function isReferencedInDatabase()
    {
        return $this->order->get_meta('_molonies_sent') ? true : false;
    }

    /**
     * Map this object properties to an array to insert/update a moloni document
     * @return array
     */
    private function mapPropsToValues()
    {
        $variables = [
            'companyId' => (int) MOLONIES_COMPANY_ID,
            'data' => [
                'fiscalZone' => $this->fiscalZone,
                'customerId' => (int) $this->customer_id,
                'documentSetId' => (int) $this->document_set_id,
                'ourReference' => $this->ourReference,
                'yourReference' => $this->yourReference,
                'maturityDateId' => ((int)MATURITY_DATE !== 0) ? (int) MATURITY_DATE : null,
                'expirationDate' => $this->expiration_date,
                'date' => $this->date,
                'notes' => $this->notes,
                'status' => $this->status,
                'products' => $this->products
            ]
        ];

        if ((defined('SHIPPING_INFO') && SHIPPING_INFO) || $this->documentType === 'billsOfLading') {
            $variables['data']['deliveryMethodId'] = (int) $this->deliveryMethodId;
            $variables['data']['deliveryLoadDate'] = $this->deliveryLoadDate;
            $variables['data']['deliveryLoadAddress'] = $this->deliveryLoadAddress;
            $variables['data']['deliveryLoadCity'] = $this->deliveryLoadCity;
            $variables['data']['deliveryLoadZipCode'] = $this->deliveryLoadZipCode;
            $variables['data']['deliveryLoadCountryId'] = (int) $this->deliveryLoadCountryId;
            $variables['data']['deliveryUnloadAddress'] = $this->deliveryUnloadAddress;
            $variables['data']['deliveryUnloadCity'] = $this->deliveryUnloadCity;
            $variables['data']['deliveryUnloadZipCode'] = $this->deliveryUnloadZipCode;
            $variables['data']['deliveryUnloadCountryId'] = (int) $this->deliveryUnloadCountryId;
        }

        if($this->documentType === "simplifiedInvoice") {
            $variables['data']['payments'] = $this->payments;
        }

        if (!empty($this->currencyExchangeId)) {
            $variables['data']['currencyExchangeId'] = (int) $this->currencyExchangeId;
            $variables['data']['currencyExchangeExchange'] = (float) $this->currencyExchangeExchange;
        }

        return $variables;
    }

    /**
     * Sends email to customer
     * @return bool|mixed
     * @throws Error
     */
    private function sendEmail()
    {
        $keyString = '';
        $mutation = [];

        $variables = [
            'companyId' => (int) MOLONIES_COMPANY_ID,
            'documents' => [
                $this->documentId
            ],
            'mailData' => [
                'to' => [
                    'name' => $this->order->get_billing_first_name() . ' ' . $this->order->get_billing_last_name(),
                    'email' => $this->order->get_billing_email()
                ],
                'message' => '',
                'attachment' => true
            ]
        ];

        switch ($this->documentType) {
            case 'invoice':
                $mutation = APIDocuments::mutationInvoiceSendMail($variables);
                $keyString= 'invoiceSendMail';
                break;
            case 'invoiceReceipt':
                $mutation = APIDocuments::mutationReceiptSendMail($variables);
                $keyString= 'receiptSendMail';
                break;
            case 'purchaseOrder':
                $mutation = APIDocuments::mutationPurchaseOrderSendMail($variables);
                $keyString= 'purchaseOrderSendMail';
                break;
            case 'proFormaInvoice':
                $mutation = APIDocuments::mutationProFormaInvoiceSendMail($variables);
                $keyString= 'proFormaInvoiceSendMail';
                break;
            case 'simplifiedInvoice':
                $mutation = APIDocuments::mutationSimplifiedInvoiceSendMail($variables);
                $keyString= 'simplifiedInvoiceSendMail';
                break;
            case 'billsOfLading':
                $mutation = APIDocuments::mutationBillsOfLadingSendMail($variables);
                $keyString= 'billsOfLadingSendMail';
                break;
        }

        if (isset($mutation['errors'])) {
            return false;
        }

        return $mutation['data'][$keyString];
    }

    /**
     * Creates document based on its type
     *
     * @return array|mixed
     *
     * @throws Error
     */
    private function createDocumentSwitch()
    {
        $keyString = '';
        $mutation = [];

        switch ($this->documentType) {
            case 'invoice':
                $mutation = APIDocuments::mutationInvoiceCreate($this->mapPropsToValues());
                $keyString= 'invoiceCreate';
                break;
            case 'invoiceReceipt':

                if (!defined('DOCUMENT_STATUS') || (int) DOCUMENT_STATUS === 0) {
                    throw new Error(__('Warning, cannot insert Invoice + Receipt documents as a draft','moloni_es'));
                }

                $mutation = (APIDocuments::mutationInvoiceCreate($this->mapPropsToValues()))['data']['invoiceCreate']['data'];

                if (!isset($mutation['documentId'])) {
                    throw new Error(sprintf(__('Warning, there was an error inserting the document %s','moloni_es'), $this->order->get_order_number()),Curl::getLog());
                }

                // Validate if the document totals match can be closed
                $orderTotal = ((float)$this->order->get_total() - (float)$this->order->get_total_refunded());
                $documentTotal = (float)$mutation['currencyExchangeTotalValue'] > 0 ? (float)$mutation['currencyExchangeTotalValue'] : (float)$mutation['totalValue'];

                if ($orderTotal !== $documentTotal) {
                    $viewUrl = admin_url('admin.php?page=molonies&action=getInvoice&id=' . $mutation['documentId']);
                    add_post_meta($this->orderId, '_molonies_sent', $mutation['documentId'], true);
                    throw new Error(
                        __('The document has been inserted but the totals do not match. ' , 'moloni_es') .
                        '<a href="' . esc_url($viewUrl) . '" target="_BLANK">' . __('View document' , 'moloni_es') . '</a>'
                    );
                }

                //close the invoice
                $this->documentType = 'invoice';
                $this->documentId = $mutation['documentId'];
                $this->closeDocument();
                //reset vars to create receipt
                $this->documentType = 'invoiceReceipt';
                $this->documentId = null;

                $variables= [
                    'companyId' => (int) MOLONIES_COMPANY_ID,
                    'data' => [
                        'documentSetId' => (int) $this->document_set_id,
                        'date' => $this->date,
                        'customerId' => (int) $this->customer_id,
                        'notes' => $this->notes,
                        'status' => 0,
                        'totalValue' => (float) $mutation['totalValue'],
                        'relatedWith' => [
                            'relatedDocumentId' => (int) $mutation['documentId'],
                            'value' => (float) $mutation['totalValue']
                        ],
                        'payments' => $this->payments
                    ]
                ];

                $mutation = APIDocuments::mutationReceiptCreate($variables);
                $keyString= 'receiptCreate';
                break;
            case 'purchaseOrder':
                $mutation = APIDocuments::mutationPurchaseOrderCreate($this->mapPropsToValues());
                $keyString= 'purchaseOrderCreate';
                break;
            case 'proFormaInvoice':
                $mutation = APIDocuments::mutationProFormaInvoiceCreate($this->mapPropsToValues());
                $keyString= 'proFormaInvoiceCreate';
                break;
            case 'simplifiedInvoice':
                $mutation = APIDocuments::mutationSimplifiedInvoiceCreate($this->mapPropsToValues());
                $keyString= 'simplifiedInvoiceCreate';
                break;
            case 'billsOfLading':
                $mutation = APIDocuments::mutationBillsOfLadingCreate($this->mapPropsToValues());
                $keyString= 'billsOfLadingCreate';
                break;
        }

        return (!isset($mutation['errors']) ? $mutation['data'][$keyString]['data'] : []);
    }

    /**
     * Creates a PDF of a document
     * @return bool
     * @throws Error
     */
    private function createPDF()
    {
        $keyString = '';
        $mutation = [];

        $variables = [
            'companyId' => (int) MOLONIES_COMPANY_ID,
            'documentId' => (int) $this->documentId,
        ];

        switch ($this->documentType) {
            case 'invoice':
                $mutation = APIDocuments::mutationInvoiceGetPDF($variables);
                $keyString= 'invoiceGetPDF';
                break;
            case 'invoiceReceipt':
                $mutation = APIDocuments::mutationReceiptGetPDF($variables);
                $keyString= 'receiptGetPDF';
                break;
            case 'purchaseOrder':
                $mutation = APIDocuments::mutationPurchaseOrderGetPDF($variables);
                $keyString= 'purchaseOrderGetPDF';
                break;
            case 'proFormaInvoice':
                $mutation = APIDocuments::mutationProFormaInvoiceGetPDF($variables);
                $keyString= 'proFormaInvoiceGetPDF';
                break;
            case 'simplifiedInvoice':
                $mutation = APIDocuments::mutationSimplifiedInvoiceGetPDF($variables);
                $keyString= 'simplifiedInvoiceGetPDF';
                break;
            case 'billsOfLading':
                $mutation = APIDocuments::mutationBillsOfLadingGetPDF($variables);
                $keyString= 'billsOfLadingGetPDF';
                break;
        }

        return (isset($mutation['data'][$keyString]) ? isset($mutation['data'][$keyString]) : false);
    }

    /**
     * Close a document based on its id
     * @return bool|mixed
     * @throws Error
     */
    private function closeDocument()
    {
        $keyString = '';
        $mutation = [];

        $variables = [
            'companyId' => (int) MOLONIES_COMPANY_ID,
            'data' => [
                'documentId' => (int) $this->documentId,
                'status' => 1
            ]
        ];

        switch ($this->documentType) {
            case 'invoice':
                $mutation = APIDocuments::mutationInvoiceUpdate($variables);
                $keyString= 'invoiceUpdate';
                break;
            case 'invoiceReceipt':
                $mutation = APIDocuments::mutationReceiptUpdate($variables);
                $keyString= 'receiptUpdate';
                break;
            case 'purchaseOrder':
                $mutation = APIDocuments::mutationPurchaseOrderUpdate($variables);
                $keyString= 'purchaseOrderUpdate';
                break;
            case 'proFormaInvoice':
                $mutation = APIDocuments::mutationProFormaInvoiceUpdate($variables);
                $keyString= 'proFormaInvoiceUpdate';
                break;
            case 'simplifiedInvoice':
                $mutation = APIDocuments::mutationSimplifiedInvoiceUpdate($variables);
                $keyString= 'simplifiedInvoiceUpdate';
                break;
            case 'billsOfLading':
                $mutation = APIDocuments::mutationBillsOfLadingUpdate($variables);
                $keyString= 'billsOfLadingUpdate';
                break;
        }

        if (isset($mutation['errors']) || !isset($mutation['data'][$keyString]['data'])) {
            return false;
        }

        return $mutation['data'][$keyString]['data'];
    }

    /**
     * Checks for warnings
     *
     * @throws Error
     */
    private function checkForWarnings()
    {
        if ((!isset($_GET['force']) || sanitize_text_field($_GET['force']) !== 'true') && $this->isReferencedInDatabase()) {
            $errorMsg = sprintf(__('The order %s document was previously generated!','moloni_es') , $this->order->get_order_number());

            if ($this->isHook === false) {
                $viewUrl = admin_url('admin.php?page=molonies&action=genInvoice&id=' . $this->orderId . '&force=true');
                $errorMsg .= " <a href='" . esc_url($viewUrl) . "'>" . __('Generate again','moloni_es') . '</a>';
            }

            throw new Error($errorMsg);
        }

        if (!isset($_GET['fiscalZone']) &&
            !empty($this->company['fiscalZone']['fiscalZone']) &&
            !empty($this->order->get_billing_country()) &&
            $this->company['fiscalZone']['fiscalZone'] !== $this->order->get_billing_country()) {
            $errorMsg = sprintf(__('The order client and your company have different fiscal zones.','moloni_es') , $this->order->get_order_number());

            if ($this->isHook === false) {
                $billingFiscalZoneUrl = admin_url('admin.php?page=molonies&action=genInvoice&id=' . $this->orderId . '&force=true&fiscalZone=billing');
                $baseFiscalZoneUrl = admin_url('admin.php?page=molonies&action=genInvoice&id=' . $this->orderId . '&force=true&fiscalZone=base');

                $errorMsg .= "<br>";
                $errorMsg .= " <a href='" . esc_url($billingFiscalZoneUrl) . "'>" . __('Use client billing fiscal zone','moloni_es') . '</a>';
                $errorMsg .= "<br>";
                $errorMsg .= " <a href='" . esc_url($baseFiscalZoneUrl) . "'>" . __('Use company fiscal zone','moloni_es') . '</a>';
            }

            throw new Error($errorMsg);
        }
    }

    /**
     * Creates url to modify document or url to download document PDF
     * @param $documentId
     * @return bool
     * @throws Error
     */
    public static function showDocument($documentId)
    {
        $variables = [
            'companyId' => (int) MOLONIES_COMPANY_ID,
            'documentId' => $documentId
        ];

        $invoice = APIDocuments::queryDocument($variables);

        if (isset($invoice['errors']) || !isset($invoice['data']['document']['data']['documentId'])) {
            return false;
        }

        $invoice = $invoice['data']['document']['data'];

        if ((int)$invoice['status'] === 1) {
            unset($variables['companyId']);

            $mutation = [];
            $keyString = '';

            switch ($invoice['documentType']['apiCode']) {
                case 'invoice':
                    $mutation = APIDocuments::queryInvoiceGetPDFToken($variables);
                    $keyString= 'invoiceGetPDFToken';
                    break;
                case 'receipt':
                    $mutation = APIDocuments::queryReceiptGetPDFToken($variables);
                    $keyString= 'receiptGetPDFToken';
                    break;
                case 'purchaseOrder':
                    $mutation = APIDocuments::queryPurchaseOrderGetPDFToken($variables);
                    $keyString= 'purchaseOrderGetPDFToken';
                    break;
                case 'proFormaInvoice':
                    $mutation = APIDocuments::queryProFormaInvoiceGetPDFToken($variables);
                    $keyString= 'proFormaInvoiceGetPDFToken';
                    break;
                case 'simplifiedInvoice':
                    $mutation = APIDocuments::querySimplifiedInvoiceGetPDFToken($variables);
                    $keyString= 'simplifiedInvoiceGetPDFToken';
                    break;
                case 'billsOfLading':
                    $mutation = APIDocuments::queryBillsOfLadingGetPDFToken($variables);
                    $keyString= 'billsOfLadingGetPDFToken';
                    break;
            }

            $result = $mutation['data'][$keyString]['data'];

            header('Location: https://mediaapi.moloni.org' . $result['path'] . '?jwt=' . $result['token']);
        } else {
            if (defined('COMPANY_SLUG')) {
                $slug = COMPANY_SLUG;
            } else {
                $slug = $invoice['company']['slug'];
            }

            header('Location: https://ac.moloni.es/' . $slug . '/' . $invoice['documentType']['apiCodePlural'] . '/view/' . $invoice['documentId']);
        }
        exit;
    }
}