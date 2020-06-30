<?php

namespace MoloniES;

use MoloniES\API\Taxes;
use MoloniES\API\Countries;
use MoloniES\API\Currencies;

/**
 * Multiple tools for handling recurring tasks
 * Class Tools
 * @package Moloni
 */
class Tools
{

    /**
     * Creates reference for product if missing
     * @param string $string
     * @param int $productId
     * @param int $variationId
     * @return string
     */
    public static function createReferenceFromString($string, $productId = 0, $variationId = 0)
    {
        $reference = '';
        $name = explode(' ', $string);

        foreach ($name as $word) {
            $reference .= '_' . mb_substr($word, 0, 3);
        }

        if ((int)$productId > 0) {
            $reference .= '_' . $productId;
        }

        if ((int)$variationId > 0) {
            $reference .= '_' . $variationId;
        }

        return $reference;
    }

    /**
     * Get a tax id given a tax rate
     * As a fallback if we don't find a tax with the same rate we return the company default
     * @param $taxRate
     * @return mixed
     * @throws Error
     */
    public static function getTaxIdFromRate($taxRate)
    {
        $defaultTax = 0;

        $variables = ['companyId' => (int) MOLONIES_COMPANY_ID];
        $taxesList = Taxes::queryTaxes($variables);

        if (!empty($taxesList) && is_array($taxesList)) {
            foreach ($taxesList as $tax) {
                if ((int)$tax['isDefault'] === 1) {
                    $defaultTax = $tax['taxId'];
                }

                if ((float)$tax['value'] === (float)$taxRate) {
                    return $tax['taxId'];
                }
            }
        }

        return $defaultTax;
    }

    /**
     * Get full tax Object given a tax rate
     * As a fallback if we don't find a tax with the same rate we return the company default
     * @param $taxRate
     * @return mixed
     * @throws Error
     */
    public static function getTaxFromRate($taxRate)
    {
        $defaultTax = 0;

        $variables = ['companyId' => (int) MOLONIES_COMPANY_ID];
        $taxesList = Taxes::queryTaxes($variables);

        if (!empty($taxesList) && is_array($taxesList)) {
            foreach ($taxesList as $tax) {
                if ((int)$tax['isDefault'] === 1) {
                    $defaultTax = $tax;
                }

                if ((float)$tax['value'] === (float)$taxRate) {
                    return $tax;
                }
            }
        }

        return $defaultTax;
    }

    /**
     * Returns country id
     * @param $countryCode
     * @return string
     * @throws Error
     */
    public static function getCountryIdFromCode($countryCode)
    {
        $variables = ['options' => [
            'search' => [
                'field' => 'iso3166_1',
                'value' => $countryCode
            ]
        ]];
        $countriesList = Countries::queryCountries($variables);

        if (!empty($countriesList) && is_array($countriesList)) {
            foreach ($countriesList as $country) {
                if (strtoupper($country['iso3166_1']) === strtoupper($countryCode)) {
                    return $country['countryId'];
                    break;
                }
            }
        }

        return '1';
    }

    /**
     * Returns currency exchange rate
     * @param int $from
     * @param int $to
     * @return array
     * @throws Error
     */
    public static function getCurrencyExchangeRate($from, $to)
    {
        $variables = [
            'options' => [
                'search' => [
                    'field' => 'pair',
                    'value' => $from . ' ' . $to
                ]
            ]
        ];

        $currenciesList = Currencies::queryCurrencyExchanges($variables);

        if (!empty($currenciesList)) {
            foreach ($currenciesList as $currency) {
                if ($currency['from']['iso4217'] === $from && $currency['to']['iso4217'] === $to) {
                    return [
                        'exchange' => $currency['exchange'],
                        'currencyExchangeId' => $currency['currencyExchangeId']
                    ];
                }
            }
        }

        return [
            'exchange' => null,
            'currencyExchangeId' => null
        ];
    }

    /**
     * @param $input
     * @return string
     */
    public static function zipCheck($input)
    {
        $zipCode = trim(str_replace(' ', '', $input));
        $zipCode = preg_replace('/[^0-9]/', '', $zipCode);
        if (strlen($zipCode) == 7) {
            $zipCode = $zipCode[0] . $zipCode[1] . $zipCode[2] . $zipCode[3] . '-' . $zipCode[4] . $zipCode[5] . $zipCode[6];
        }
        if (strlen($zipCode) == 6) {
            $zipCode = $zipCode[0] . $zipCode[1] . $zipCode[2] . $zipCode[3] . '-' . $zipCode[4] . $zipCode[5] . '0';
        }
        if (strlen($zipCode) == 5) {
            $zipCode = $zipCode[0] . $zipCode[1] . $zipCode[2] . $zipCode[3] . '-' . $zipCode[4] . '00';
        }
        if (strlen($zipCode) == 4) {
            $zipCode = $zipCode . '-' . '000';
        }
        if (strlen($zipCode) == 3) {
            $zipCode = $zipCode . '0-' . '000';
        }
        if (strlen($zipCode) == 2) {
            $zipCode = $zipCode . '00-' . '000';
        }
        if (strlen($zipCode) == 1) {
            $zipCode = $zipCode . '000-' . '000';
        }
        if (strlen($zipCode) == 0) {
            $zipCode = '1000-100';
        }
        if (self::finalCheck($zipCode)) {
            return $zipCode;
        }

        return '1000-100';
    }

    /**
     * Validate a Zip Code format
     * @param string $zipCode
     * @return bool
     */
    private static function finalCheck($zipCode)
    {
        $regexp = "/[0-9]{4}\-[0-9]{3}/";
        if (preg_match($regexp, $zipCode)) {
            return (true);
        }

        return (false);
    }
}
