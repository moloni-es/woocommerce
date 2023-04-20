<?php

namespace MoloniES;

use MoloniES\API\FiscalZone;
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
     *
     * @param string $string
     * @param int $productId
     * @param int $variationId
     *
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
     * Create moloni tax based on value and country code
     *
     * @param float $taxRate Tax value
     * @param string $countryCode Country code
     *
     * @return array
     *
     * @throws Error
     */
    public static function createTaxFromRateAndCode($taxRate, $countryCode = 'es') {
        $taxCreateVariables = [
            'data' => [
                'visible' => 1,
                'name' => 'VAT - ' . strtoupper($countryCode) . ' - ' . $taxRate . '%',
                'fiscalZone' => $countryCode,
                'countryId' => self::getCountryIdFromCode($countryCode),
                'type' => 1,
                'fiscalZoneFinanceType' => 1,
                'isDefault' => false,
                'value' => (float)$taxRate
            ]
        ];
        $countryCodeTaxSettingsVariables = [
            'fiscalZone' => $countryCode
        ];

        $countryCodeTaxSettings = FiscalZone::queryFiscalZoneTaxSettings($countryCodeTaxSettingsVariables);
        $countryCodeTaxSettings = $countryCodeTaxSettings['data']['fiscalZoneTaxSettings']['fiscalZoneModes'][0]; // Remove some nodes from array

        if (($countryCodeTaxSettings['visible'] === 'TYPESELECTED' || $countryCodeTaxSettings['visible'] === 'ALWAYS') &&
            $countryCodeTaxSettings['type'] === 'VALUES') {
            $taxCreateVariables['data']['fiscalZoneFinanceTypeMode'] = 'NOR';
        }

        return Taxes::mutationTaxCreate($taxCreateVariables);
    }

    /**
     * Get full tax Object given a tax rate
     * As a fallback if we don't find a tax with the same rate we return the company default
     *
     * @param float $taxRate Tax value
     * @param string $countryCode Country code
     *
     * @return mixed
     *
     * @throws Error
     */
    public static function getTaxFromRate($taxRate, $countryCode = 'es')
    {
        $moloniTax = [];
        $countryCode = strtolower((string)$countryCode);

        $queryVariables = [
            'options' => [
                'filter' => [
                    [
                        'field' => 'value',
                        'comparison' => 'eq',
                        'value' => (string)$taxRate
                    ],
                    [
                        'field' => 'flags',
                        'comparison' => 'eq',
                        'value' => '0'
                    ]
                ],
                'search' => [
                    'field' => 'fiscalZone',
                    'value' => $countryCode
                ]
            ]
        ];

        $taxes = Taxes::queryTaxes($queryVariables);

        if (!empty($taxes) && isset($taxes[0]['taxId'])) {
            $moloniTax = $taxes[0];
        }

        if (empty($moloniTax)) {
            $moloniTax = self::createTaxFromRateAndCode($taxRate, $countryCode);
        }

        return $moloniTax;
    }

    /**
     * Returns country id
     * @param $countryCode
     * @return string
     * @throws Error
     */
    public static function getCountryIdFromCode($countryCode)
    {
        $variables = [
            'options' => [
                'filter' => [
                    'field' => 'iso3166_1',
                    'comparison' => 'eq',
                    'value' => $countryCode
                ]
            ]
        ];
        $countryId = \MoloniES\Enums\Countries::SPAIN;

        $countriesList = Countries::queryCountries($variables);

        if (isset($countriesList['data']['countries']['data']) &&
            is_array($countriesList['data']['countries']['data']) ) {
            foreach ($countriesList['data']['countries']['data'] as $country) {
                if (strtoupper($country['iso3166_1']) === strtoupper($countryCode)) {
                    $countryId = $country['countryId'];

                    break;
                }
            }
        }

        return $countryId;
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
