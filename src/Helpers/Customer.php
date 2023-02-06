<?php

namespace MoloniES\Helpers;

class Customer
{
    public static function isVatEsValid($vat = '')
    {
        // NIF types in which the control digit must be numeric
        $controlMustBeNumeric = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'U', 'V'];
        // NIF types in which the control digit must be alphabetic
        $controlMustBeAlphabetic = ['P', 'Q', 'R', 'S', 'N', 'W'];
        // NIF types which we have no information if it must be numeric/alphabetic, so we check for both
        $controlIsUndefined = ['K', 'L', 'M', 'X', 'Y', 'Z'];
        // Array of the correspondent Letter for each Index
        $relationArray = ['J', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

        $i = 0;

        if (preg_match("/^(([a-w|A-W]{1}\d{7}[a-z|A-Z]{1})|([a-w|A-W]{1}\d{8}))$/", $vat)) {
            $evenA = 0;

            for ($i = 2; $i <= 6; $i += 2) {
                $evenA += (int)($vat[$i]);
            }

            $oddB = 0;
            $temp = 0;

            for ($i = 1; $i <= 7; $i += 2) {
                $temp = (2 * (int)($vat[$i]));

                if ($temp > 9) {
                    $temp = 1 + ($temp - 10);
                }

                $oddB += $temp;
            }

            $sumC = $evenA + $oddB;
            $unitiesE = $sumC % 10;
            $finalRelationD = ($unitiesE === 0 ? 0 : 10 - $unitiesE);

            if (in_array($vat[0], $controlMustBeNumeric)) {
                if ((int)($vat[8]) === $finalRelationD) {
                    return true;
                }

                return false;
            }

            if (in_array($vat[0], $controlMustBeAlphabetic)) {
                if ($vat[8] === $relationArray[$finalRelationD]) {
                    return true;
                }

                return false;
            }

            if (in_array($vat[0], $controlIsUndefined)) {
                if ((int)$vat[8] === $finalRelationD || $vat[8] === $relationArray[$finalRelationD]) {
                    return true;
                }

                return false;
            }

            return false;
        }

        if (preg_match("/^(\d{8}[a-z|A-Z]{1})$/", $vat)) {
            $firstNumbers = substr($vat, 0, -1);
            $relationString = 'TRWAGMYFPDXBNJZSQVHLCKE';

            if ($vat[8] === $relationString[(int)$firstNumbers % 23]) {
                return true;
            }

            return false;
        }

        if (preg_match("/^(([x-z|X-Z]{1}\d{7}[a-z|A-Z]{1}))$/", $vat)) {
            // for types X, Y and Z its stated that X -> 0, Y -> 1, Z -> 2
            $relativeNumber = '';

            if ($vat[0] === 'X') {
                $relativeNumber = '0';
            } else if ($vat[0] === 'Y') {
                $relativeNumber = '1';
            } else {
                $relativeNumber = '2';
            }

            $relativeVat = $relativeNumber . substr($vat, 1);
            $firstNumbers = substr($relativeVat, 0, -1);
            $relationString = 'TRWAGMYFPDXBNJZSQVHLCKE';

            if ($vat[8] === $relationString[(int)$firstNumbers % 23]) {
                return true;
            }

            return false;
        }

        if (preg_match("/^(([x|X]{1}\d{8}[a-z|A-Z]{1}))$/", $vat)) {
            // looks like old format of type X NIFS so go ahead do your thing
            return true;
        }

        return false;
    }
}