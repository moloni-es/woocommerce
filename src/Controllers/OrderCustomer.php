<?php

namespace MoloniES\Controllers;

use WC_Order;
use MoloniES\Exceptions\DocumentError;
use MoloniES\API\Customers;
use MoloniES\Enums\Countries;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Helpers\Customer;
use MoloniES\Tools;

class OrderCustomer
{
    /**
     * @var WC_Order
     */
    private $order;

    private $customer_id = false;
    private $vat = null;
    private $email = '';
    private $name = 'Cliente';
    private $contactName = '';
    private $zipCode = '10000';
    private $address = 'Desconocido';
    private $city = 'Desconocido';
    private $languageId = 2;
    private $countryId = 1;

    /**
     * List of some invalid vat numbers
     * @var array
     */
    private $invalidVats = [
        '999999999',
        '000000000',
        '111111111'
    ];

    public function __construct(WC_Order $order)
    {
        $this->order = $order;
    }

    /**
     * Save client
     *
     * @return bool|int
     *
     * @throws DocumentError
     */
    public function create()
    {
        $this->countryId = $this->getCustomerCountryId();
        $this->languageId = $this->getCustomerLanguageId();
        $this->email = $this->order->get_billing_email();
        $this->vat = $this->getVatNumber();

        $variables = [
            'data' => [
                'name' => $this->getCustomerName(),
                'address' => $this->getCustomerBillingAddress(),
                'zipCode' => $this->getCustomerZip(),
                'city' => $this->getCustomerBillingCity(),
                'countryId' => $this->countryId,
                'languageId' => $this->languageId,
                'email' => $this->email,
                'phone' => $this->order->get_billing_phone(),
                'contactName' => $this->contactName,
                'maturityDateId' => defined('MATURITY_DATE') && (int) MATURITY_DATE > 0 ? (int) MATURITY_DATE : null,
                'paymentMethodId' => defined('PAYMENT_METHOD') && (int) PAYMENT_METHOD > 0? (int) PAYMENT_METHOD : null
            ]
        ];

        $customerExists = $this->searchForCustomer();

        if (empty($variables['data']['email'])) {
            unset($variables['data']['email']);
        }

        if (empty($customerExists)){
            $variables['data']['vat'] = $this->vat;
            $variables['data']['number'] = self::getCustomerNextNumber();

            try {
                $result = Customers::mutationCustomerCreate($variables);
            } catch (APIExeption $e) {
                throw new DocumentError(
                    __('Error creating customer.', 'moloni_es'),
                    [
                        'message' => $e->getMessage(),
                        'data' => $e->getData()
                    ]
                );
            }

            $keyString = 'customerCreate';
        } else {
            $variables['data']['customerId'] = (int)$customerExists['customerId'];

            try {
                $result = Customers::mutationCustomerUpdate($variables);
            } catch (APIExeption $e) {
                throw new DocumentError(
                    __('Error updating customer.','moloni_es'),
                    [
                        'message' => $e->getMessage(),
                        'data' => $e->getData()
                    ]
                );
            }

            $keyString = 'customerUpdate';
        }

        if (isset($result['data'][$keyString]['data']['customerId'])) {
            $this->customer_id = $result['data'][$keyString]['data']['customerId'];
        } else {
            throw new DocumentError(__('There was an error saving the customer.','moloni_es'));
        }

        return $this->customer_id;
    }

    /**
     * Get the vat number of an order
     * Get it from a custom field and validate if Portuguese
     *
     * @return string
     */
    public function getVatNumber(): ?string
    {
        $vat = null;

        if (defined('VAT_FIELD')) {
            $metaVat = trim($this->order->get_meta(VAT_FIELD));

            if (!empty($metaVat)) {
                $vat = $metaVat;
            }
        }

        // Do some more verifications if the vat number is Portuguese
        if ($this->countryId === Countries::PORTUGAL) {
            // Remove the PT part from the beginning
            if (stripos($vat, strtoupper('PT')) === 0) {
                $vat = str_ireplace('PT', '', $vat);
            }

            // Check if the vat is one of this
            if (empty($vat) || in_array($vat, $this->invalidVats)) {
                $vat = null;
            }
        }

        // Do some more verifications if the vat number is Spanish
        if ($this->countryId === Countries::SPAIN) {
            if (stripos($vat, strtoupper('ES')) === 0) {
                $vat = str_ireplace('ES', '', $vat);
            }

            if (empty($vat) || in_array($vat, $this->invalidVats)) {
                $vat = null;
            }

            if (!empty($vat) && !Customer::isVatEsValid($vat)) {
                $vat = null;
            }
        }

        return $vat;
    }

    /**
     * Checks if the cohasmpany name is set
     * If they order  a company we issue the document to the company
     * And add the name of the person to the contact name
     *
     * @return string
     */
    public function getCustomerName(): string
    {
        $billingName = $this->order->get_billing_first_name();
        $billingLastName = $this->order->get_billing_last_name();
        if (!empty($billingLastName)) {
            $billingName .= ' ' . $this->order->get_billing_last_name();
        }

        $billingCompany = trim($this->order->get_billing_company());
        if (!empty($billingCompany)) {
            $this->name = $billingCompany;
            $this->contactName = $billingName;
        } elseif (!empty($billingName)) {
            $this->name = $billingName;
        }


        return $this->name;
    }

    /**
     * Create a customer billing an address
     *
     * @return string
     */
    public function getCustomerBillingAddress(): string
    {
        $billingAddress = trim($this->order->get_billing_address_1());
        $billingAddress2 = $this->order->get_billing_address_2();

        if (!empty($billingAddress2)) {
            $billingAddress .= ' ' . trim($billingAddress2);
        }

        if (!empty($billingAddress)) {
            $this->address = $billingAddress;
        }

        return $this->address;
    }

    /**
     * Create a customer billing City
     *
     * @return string
     */
    public function getCustomerBillingCity(): string
    {
        $billingCity = trim($this->order->get_billing_city());

        if (!empty($billingCity)) {
            $this->city = $billingCity;
        }

        return $this->city;
    }

    /**
     * Gets the zip code of a customer
     * If the customer is Portuguese validate the Vat Number
     *
     * @return string
     */
    public function getCustomerZip(): string
    {
        $this->zipCode = $this->order->get_billing_postcode();

        return $this->zipCode;
    }

    /**
     * Get the customer next available number for incremental inserts
     *
     * @return int
     */
    public static function getCustomerNextNumber()
    {
        $neddle = defined('CLIENT_PREFIX') ? CLIENT_PREFIX : '';
        $neddle .= '%';

        $variables = [
            'options' => [
                'filter' => [
                    'field' => 'number',
                    'comparison' => 'like',
                    'value' => $neddle
                ]
            ]
        ];

        $nextNumber = '';

        try {
            $query = Customers::queryCustomerNextNumber($variables);

            if (isset($query['data']['customerNextNumber']['data'])) {
                $nextNumber = $query['data']['customerNextNumber']['data'];
            }
        } catch (APIExeption $e) {}

        if (empty($nextNumber)) {
            $nextNumber = defined('CLIENT_PREFIX') ? CLIENT_PREFIX : '';
            $nextNumber .= '1';
        }

        return $nextNumber;
    }

    /**
     * Get the country_id based on a ISO value
     *
     * @return int
     *
     * @throws DocumentError
     */
    public function getCustomerCountryId(): int
    {
        $countryCode = $this->order->get_billing_country();

        try {
            $id = (int)Tools::getCountryIdFromCode($countryCode);
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error fetching country'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        }

        return $id;
    }

    /**
     * If the country of the customer is one of the available we set it to Portuguese
     */
    public function getCustomerLanguageId(): int
    {
        return $this->countryId === Countries::PORTUGAL ? 1 : 2;
    }

    /**
     * Search for a customer based on $this->vat or $this->email
     *
     * @return bool|array
     *
     * @throws DocumentError
     */
    public function searchForCustomer()
    {
        $result = false;

        $variables = [
            'options' => [
                'filter' => [
                    'field' => '',
                    'comparison' => 'eq',
                    'value' => ''
                ]
            ]
        ];

        if (!empty($this->vat)) {
            $variables['options']['filter']['field'] = 'vat';
            $variables['options']['filter']['value'] = $this->vat;
        } else if (!empty($this->email)) {
            $variables['options']['filter']['field'] = 'email';
            $variables['options']['filter']['value'] = $this->email;
        }

        try {
            $searchResult = Customers::queryCustomers($variables);
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error fetching customers.', 'moloni_es'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        }

        if (isset($searchResult['data']['customers']['data'][0]['customerId'])) {
            $result = $searchResult['data']['customers']['data'][0];
        }

        return $result;
    }
}
