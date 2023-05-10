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

    public const TYPES_NAMES = [
        self::INVOICE => 'Invoice',
        self::RECEIPT => 'Receipt',
        self::PURCHASE_ORDER => 'Purchase Order',
        self::PRO_FORMA_INVOICE => 'Pro Forma Invoice',
        self::SIMPLIFIED_INVOICE => 'Simplified invoice',
        self::ESTIMATE => 'Budget',
        self::BILLS_OF_LADING => 'Bills of lading',
    ];

    public const AVAILABLE_TYPES = [
        self::INVOICE => 'Invoice',
        self::INVOICE_AND_RECEIPT => 'Invoice + Receipt',
        self::PURCHASE_ORDER => 'Purchase Order',
        self::PRO_FORMA_INVOICE => 'Pro Forma Invoice',
        self::SIMPLIFIED_INVOICE => 'Simplified invoice',
        self::ESTIMATE => 'Budget',
        self::BILLS_OF_LADING => 'Bills of lading',
    ];

    public static function getDocumentTypeName(?string $documentType = ''): string
    {
        return self::TYPES_NAMES[$documentType] ?? '';
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