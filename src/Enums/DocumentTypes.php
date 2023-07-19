<?php

namespace MoloniES\Enums;

class DocumentTypes
{
    public const INVOICE = 'invoice';
    public const RECEIPT = 'receipt';
    public const INVOICE_AND_RECEIPT = 'invoiceAndReceipt';
    public const SIMPLIFIED_INVOICE = 'simplifiedInvoice';
    public const BILLS_OF_LADING = 'billsOfLading';
    public const PURCHASE_ORDER = 'purchaseOrder';
    public const ESTIMATE = 'estimate';
    public const PRO_FORMA_INVOICE = 'proFormaInvoice';

    public const TYPES_WITH_PAYMENTS = [
        self::RECEIPT,
        self::PRO_FORMA_INVOICE,
        self::SIMPLIFIED_INVOICE,
    ];

    public const TYPES_WITH_DELIVERY = [
        self::INVOICE,
        self::PURCHASE_ORDER,
        self::PRO_FORMA_INVOICE,
        self::SIMPLIFIED_INVOICE,
        self::ESTIMATE,
        self::BILLS_OF_LADING,
    ];

    public const TYPES_REQUIRES_DELIVERY = [
        self::BILLS_OF_LADING,
    ];

    public const TYPES_WITH_PRODUCTS = [
        self::INVOICE,
        self::PURCHASE_ORDER,
        self::PRO_FORMA_INVOICE,
        self::SIMPLIFIED_INVOICE,
        self::ESTIMATE,
        self::BILLS_OF_LADING,
    ];

    public static function getForRender(): array
    {
        return [
            self::INVOICE => __('Invoice', 'moloni_es'),
            self::INVOICE_AND_RECEIPT => __('Invoice + Receipt', 'moloni_es'),
            self::PURCHASE_ORDER => __('Purchase Order', 'moloni_es'),
            self::PRO_FORMA_INVOICE => __('Pro Forma Invoice', 'moloni_es'),
            self::SIMPLIFIED_INVOICE => __('Simplified invoice', 'moloni_es'),
            self::ESTIMATE => __('Budget', 'moloni_es'),
            self::BILLS_OF_LADING => __('Bills of lading', 'moloni_es')
        ];
    }

    public static function getDocumentTypeName(?string $documentType = ''): string
    {
        switch ($documentType) {
            case self::INVOICE:
                return __('Invoice', 'moloni_es');
            case self::RECEIPT:
                return __('Receipt', 'moloni_es');
            case self::INVOICE_AND_RECEIPT:
                return __('Invoice + Receipt', 'moloni_es');
            case self::PURCHASE_ORDER:
                return __('Purchase Order', 'moloni_es');
            case self::PRO_FORMA_INVOICE:
                return __('Pro Forma Invoice', 'moloni_es');
            case self::SIMPLIFIED_INVOICE:
                return __('Simplified Invoice', 'moloni_es');
            case self::ESTIMATE:
                return __('Budget', 'moloni_es');
            case self::BILLS_OF_LADING:
                return __('Bill of lading', 'moloni_es');
        }

        return $documentType;
    }

    public static function hasPayments(?string $documentType = ''): bool
    {
        return in_array($documentType, self::TYPES_WITH_PAYMENTS, true);
    }

    public static function hasProducts(?string $documentType = ''): bool
    {
        return in_array($documentType, self::TYPES_WITH_PRODUCTS, true);
    }

    public static function hasDelivery(?string $documentType = ''): bool
    {
        return in_array($documentType, self::TYPES_WITH_DELIVERY, true);
    }

    public static function requiresDelivery(?string $documentType = ''): bool
    {
        return in_array($documentType, self::TYPES_REQUIRES_DELIVERY, true);
    }
}
