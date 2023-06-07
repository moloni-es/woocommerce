<?php

namespace MoloniES\Helpers;

class References
{
    public const IGNORED_REFERENCES = [
        'shipping',
        'envio',
        'envío',
        'fee',
        'tarifa'
    ];

    public static function isIgnoredReference(string $reference): bool
    {
        return in_array(strtolower($reference), self::IGNORED_REFERENCES);
    }
}
