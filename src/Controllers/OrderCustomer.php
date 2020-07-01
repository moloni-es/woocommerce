<?php

namespace MoloniES\Controllers;

use MoloniES\API\Customers;
use MoloniES\Error;
use MoloniES\Tools;
use WC_Order;

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
    private $languageId = 1;
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

    /**
     * Documents constructor.
     * @param WC_Order $order
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * @return bool|int
     * @throws Error
     */
    public function create()
    {
        $this->vat = $this->getVatNumber();
        $this->email = $this->order->get_billing_email();

        $variables = ['companyId' => (int) MOLONIES_COMPANY_ID,
            'data' => [
                'name' => $this->getCustomerName(),
                'languageId' => (int) $this->getCustomerLanguageId(),
                'address' => $this->getCustomerBillingAddress(),
                'zipCode' => $this->getCustomerZip(),
                'city' => $this->getCustomerBillingCity(),
                'countryId' => (int) $this->getCustomerCountryId(),
                'email' => $this->order->get_billing_email(),
                'phone' => $this->order->get_billing_phone(),
                'contactName' => $this->contactName,
                'maturityDateId' => defined('MATURITY_DATE') && (int) MATURITY_DATE > 0 ? (int) MATURITY_DATE : null,
                'paymentMethodId' => defined('PAYMENT_METHOD') && (int) PAYMENT_METHOD > 0? (int) PAYMENT_METHOD : null
            ]
        ];

        $customerExists = $this->searchForCustomer();
        if (!$customerExists) {
            $variables['data']['vat'] = $this->vat;
            $variables['data']['number'] = self::getCustomerNextNumber();
            $result = Customers::mutationCustomerCreate($variables);
            $keyString = 'customerCreate';
        } else {
            $variables['data']['customerId'] = (int) $customerExists['customerId'];
            $result = Customers::mutationCustomerUpdate($variables);
            $keyString = 'customerUpdate';
        }

        if (isset($result['data'][$keyString]['data']['customerId'])) {
            $this->customer_id = $result['data'][$keyString]['data']['customerId'];
        } else {
            throw new Error(__('Warning, there was an error inserting the customer.','moloni_es'));
        }

        return $this->customer_id;
    }

    /**
     * Get the vat number of an order
     * Get it from a custom field and validate if Portuguese
     * @return string
     */
    public function getVatNumber()
    {
        $vat = null;

        if (defined('VAT_FIELD')) {
            $metaVat = trim($this->order->get_meta(VAT_FIELD));

            if (!empty($metaVat)) {
                $vat = $metaVat;
            }
        }

        $billingCountry = $this->order->get_billing_country();

        // Do some more verifications if the vat number is Portuguese
        if ($billingCountry === 'PT') {
            // Remove the PT part from the beginning
            if (stripos($vat, strtoupper('PT')) === 0) {
                $vat = str_ireplace('PT', '', $vat);
            }

            // Check if the vat is one of this
            if (empty($vat) || in_array($vat, $this->invalidVats, false)) {
                $vat = null;
            }
        }

        return $vat;
    }

    /**
     * Checks if the cohasmpany name is set
     * If the order  a company we issue the document to the company
     * And add the name of the person to the contact name
     * @return string
     */
    public function getCustomerName()
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
     * Create a customer billing a address
     * @return string
     */
    public function getCustomerBillingAddress()
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
     * @return string
     */
    public function getCustomerBillingCity()
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
     * @return string
     */
    public function getCustomerZip()
    {
        $zipCode = $this->order->get_billing_postcode();

        if ($this->order->get_billing_country() === 'PT') {
            $zipCode = Tools::zipCheck($zipCode);
        }

        $this->zipCode = $zipCode;
        return $this->zipCode;
    }

    /**
     * Get the customer next available number for incremental inserts
     * @return int
     * @throws Error
     */
    public static function getCustomerNextNumber()
    {
        $variables = [
            'companyId' => (int) MOLONIES_COMPANY_ID,
            'options' => [
                'filter' => [
                    'field' => 'number',
                    'comparison' => 'like',
                    'value' => CLIENT_PREFIX . '%'
                ],
                'order' => [
                    'field' => 'createdAt',
                    'sort' => 'DESC'
                ],
                'pagination' => [
                    'page' => 1,
                    'qty' => 1
                ]
            ]
        ];

        $result = (Customers::queryCustomers($variables))['data']['customers']['data'];

        if (empty($result)) {
            $nextNumber = CLIENT_PREFIX . '1';
        } else {
            //go straight for the first result because we only ask for 1
            $lastNumber = substr($result[0]['number'], strlen(CLIENT_PREFIX));
            $nextNumber = CLIENT_PREFIX . ((int) $lastNumber + 1);
        }

        return $nextNumber;
    }

    /**
     * Get the country_id based on a ISO value
     * @return int
     * @throws Error
     */
    public function getCustomerCountryId()
    {
        $countryCode = $this->order->get_billing_country();
        $this->countryId = Tools::getCountryIdFromCode($countryCode);

        return $this->countryId;
    }

    /**
     * If the country of the customer is one of the available we set it to Portuguese
     */
    public function getCustomerLanguageId()
    {
        $this->languageId = in_array($this->countryId, [1]) ? 1 : 2;
        return $this->languageId;
    }

    /**
     * Search for a customer based on $this->vat or $this->email
     * @param string|bool $forField
     * @return bool
     * @throws Error
     */
    public function searchForCustomer()
    {
        $result = false;

        $variables = [
            'companyId' => (int) MOLONIES_COMPANY_ID,
            'options' => [
                'filter' => [
                    'field' => '',
                    'comparison' => 'eq',
                    'value' => ''
                ]
            ]
        ];

        if ($this->vat !== null) {
            $variables['options']['filter']['field'] = 'vat';
            $variables['options']['filter']['value'] = $this->vat;

            $searchResult = Customers::queryCustomers($variables);

            if (isset($searchResult['data']['customers']['data'][0]['customerId'])) {
                $result = $searchResult['data']['customers']['data'][0];
            }
        } else if (!empty($this->email)) {
            $variables['options']['filter']['field'] = 'email';
            $variables['options']['filter']['value'] = $this->email;

            $searchResult = Customers::queryCustomers($variables);

            if (isset($searchResult['data']['customers']['data'][0]['customerId'])) {
                $result = $searchResult['data']['customers']['data'][0];
            }
        }

        return $result;
    }
}
