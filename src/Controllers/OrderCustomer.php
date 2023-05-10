<?php

namespace MoloniES\Controllers;

use MoloniES\API\Customers;
use MoloniES\Enums\Countries;
use MoloniES\Exceptions\Error;
use MoloniES\Helpers\Customer;
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
            $result = Customers::mutationCustomerCreate($variables);
            $keyString = 'customerCreate';
        } else {
            $variables['data']['customerId'] = (int)$customerExists['customerId'];
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

        try {
            $query = Customers::queryCustomerNextNumber($variables);

            if (!isset($query['data']['customerNextNumber']['data'])) {
                throw new Error('Something went wrong!');
            }

            $nextNumber = $query['data']['customerNextNumber']['data'];
        } catch (Error $e) {
            $nextNumber = defined('CLIENT_PREFIX') ? CLIENT_PREFIX : '';
            $nextNumber .= '1';
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

        return (int)Tools::getCountryIdFromCode($countryCode);
    }

    /**
     * If the country of the customer is one of the available we set it to Portuguese
     */
    public function getCustomerLanguageId()
    {
        return $this->countryId === Countries::PORTUGAL ? 1 : 2;
    }

    /**
     * Search for a customer based on $this->vat or $this->email
     *
     * @return bool|array
     *
     * @throws Error
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
    
    private static function sugereProximoNumero($str, $nums_ocupados = [])
    {
        $str_separada = str_split($str);

        $prefixo = '';
        $sufixo = '';
        $found_int = false;

        $leading_zeroes = false;
        $num_pad_left = 0;

        $suposto_inteiro = '';

        // o último	conjunto de inteiros apanhados vai para o $suposto_inteiro todos os anteriomente apanhados vão para o $prefixo
        // "KJ43HKLJH987IUY" = ($prefixo => "KJ43HKLJH", $sufixo => "IUY", $suposto_inteiro => "987")
        foreach ($str_separada as $char) {
            if (is_numeric($char)) {
                if ($sufixo !== '') {
                    if ($prefixo === '') {
                        $prefixo = $suposto_inteiro . $sufixo;
                        $sufixo = '';
                        $suposto_inteiro = $char;
                        continue;
                    }
                    $sufixo .= $char;
                    continue;
                }
                $found_int = true;
                $suposto_inteiro .= $char;
            } else if ($found_int) {
                $sufixo .= $char;
            } else {
                $prefixo .= $char;
            }
        }

        if (strlen($suposto_inteiro) > 0) {
            $inteiro_sep = str_split($suposto_inteiro);

            // aqui verificam-se os 0 à esquerda para no fim acrecentar
            if (strcmp($inteiro_sep[0], "0") === 0) {
                $num_pad_left = count($inteiro_sep);
                $leading_zeroes = true;
            }

            $suposto_inteiro = ((int)$suposto_inteiro + 1);
            $next = $prefixo . $suposto_inteiro . $sufixo;

            if (is_array($nums_ocupados) && !empty($nums_ocupados)) {

                $new_nums_ocupados = array();
                foreach ($nums_ocupados as $v) {
                    $new_nums_ocupados[$v] = 1;
                }

                //para termos a certeza absoluta que não vai ficar aqui "ad aeternum"
                $iteration = 0;
                while (isset($new_nums_ocupados[$next])) {
                    $suposto_inteiro += 1;
                    $next = ($prefixo . ((int)$suposto_inteiro) . $sufixo);

                    $iteration += 1;
                    if ($iteration > 1000) {
                        return '';
                    }
                }
            }

            // caso existam 0 à esquerda acrescentar
            if ($leading_zeroes) {
                $next = ($prefixo . (str_pad((int)$suposto_inteiro, $num_pad_left, "0", STR_PAD_LEFT)) . $sufixo);
            }

            return $next;
        }

        return self::sugereProximoNumero($str . '0', $nums_ocupados);
    }
}
