<?php

namespace App\Services;

class PhoneNormalizer
{
    public function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '55')) {
            return $digits;
        }

        if (strlen($digits) >= 10) {
            return '55'.$digits;
        }

        return $digits;
    }
}
