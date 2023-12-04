<?php

namespace MoloniES;

use MoloniES\API\Countries;
use MoloniES\API\Currencies;
use MoloniES\API\FiscalZone;
use MoloniES\API\Taxes;
use MoloniES\Enums\Languages;
use MoloniES\Exceptions\APIExeption;

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
    public static function createReferenceFromString($string, $productId = 0, $variationId = 0): string
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
     * @throws APIExeption
     */
    public static function createTaxFromRateAndCode($taxRate, $countryCode = 'es'): array
    {
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
     * @throws APIExeption
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
     *
     * @param $countryCode
     *
     * @return string
     *
     * @throws APIExeption
     */
    public static function getCountryIdFromCode($countryCode): string
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
        $countryId = Enums\Countries::SPAIN;

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
     * Returns country id
     *
     * @param string $countryIso
     *
     * @return array
     *
     * @throws APIExeption
     */
    public static function getMoloniCountryByCode(string $countryIso = ''): array
    {
        $default = [
            'countryId' => Enums\Countries::SPAIN,
            'languageId' => Languages::ES,
            'code' => strtoupper($countryIso)
        ];

        /** Early return */
        if (empty($countryIso)) {
            return $default;
        }

        $variables = [
            'options' => [
                'search' => [
                    'field' => 'iso3166_1',
                    'value' => $countryIso,
                ],
                'order' => [
                    [
                        'field' => 'ordering',
                        'sort' => 'ASC'
                    ]
                ],
                'defaultLanguageId' => Languages::EN
            ],
        ];

        $targetCountries = Countries::queryCountries($variables)['data']['countries']['data'] ?? [];

        /** Early return */
        if (empty($targetCountries)) {
            return $default;
        }

        /** Return first */
        return [
            'countryId' => (int)$targetCountries[0]['countryId'],
            'languageId' => (int)$targetCountries[0]['language']['languageId'],
            'code' => strtoupper($countryIso)
        ];
    }

    /**
     * Returns currency exchange rate
     *
     * @param int $from
     * @param int $to
     *
     * @return array
     *
     * @throws APIExeption
     */
    public static function getCurrencyExchangeRate($from, $to): array
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
}
