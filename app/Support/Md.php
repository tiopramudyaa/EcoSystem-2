<?php

namespace App\Support;

/**
 * Display format for man days / Resolution Days / quota numbers.
 * 5 -> "5", 1.5 -> "1.5", 0.25 -> "0.25" (up to 2 decimals, trailing zeros dropped).
 */
class Md
{
    public static function format(mixed $value, string $empty = '-'): string
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return $empty;
        }

        $s = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');

        return $s === '-0' || $s === '' ? '0' : $s;
    }
}
