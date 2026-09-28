<?php

namespace App\Support;

class PhoneMatcher
{
    public static function digits(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?: '';
    }

    /** Compara telefones BR (10/11 dígitos, com ou sem 55 / 9º dígito). */
    public static function matches(?string $a, ?string $b): bool
    {
        $left = self::normalize($a);
        $right = self::normalize($b);
        if ($left === '' || $right === '') {
            return false;
        }
        if ($left === $right) {
            return true;
        }

        return self::local($left) === self::local($right);
    }

    public static function normalize(?string $phone): string
    {
        $d = self::digits($phone);
        if (str_starts_with($d, '55') && strlen($d) >= 12) {
            $d = substr($d, 2);
        }

        return $d;
    }

    public static function local(string $digits): string
    {
        if (strlen($digits) === 11 && $digits[2] === '9') {
            return substr($digits, 0, 2).substr($digits, 3);
        }

        return $digits;
    }
}
