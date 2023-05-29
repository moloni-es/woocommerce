<?php

namespace MoloniES\Services\MoloniProduct\Helpers\Abstracts;

abstract class VariantHelperAbstract
{
    protected function findInName(array $array, string $needle)
    {
        foreach ($array as $key => $value) {
            if ($value['name'] === $needle) {
                return $key;
            }
        }

        return false;
    }

    protected function findInCode(array $array, string $needle)
    {
        foreach ($array as $value) {
            $value['code'] = $this->cleanReferenceString($value['code']);

            if ($value['code'] === $needle) {
                return $value;
            }
        }

        return false;
    }

    protected function findInPropertyGroup(array $array, int $needle)
    {
        foreach ($array as $value) {
            if ((int)$value['propertyGroupId'] === $needle) {
                return $value;
            }
        }

        return false;
    }

    protected function cleanReferenceString(string $string, int $truncate = 30): string
    {
        return substr($this->cleanCodeString($string), 0, $truncate);
    }

    protected function cleanCodeString(string $string): string
    {
        //Remove end and start spacing
        $string = trim($string);

        // All chars upper case
        $string = strtoupper($string);

        // Remove special chars
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);

        // Replaces all double spaces left
        // Replaces all spaces with hyphens
        return str_replace(['  ', ' '], [' ', '-'], $string);
    }

    protected function getNextPropertyOrder(?array $properties = []): int
    {
        $lastOrder = 0;

        if (!empty($properties)) {
            $count = count($properties);
            $lastIndex = $count - 1;

            $lastOrder = $properties[$lastIndex]['ordering'] ?? 0;
        }

        return $lastOrder + 1;
    }
}