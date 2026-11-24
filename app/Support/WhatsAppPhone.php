<?php

namespace App\Support;

final class WhatsAppPhone
{
    /**
     * Normalize a stored or incoming number to E.164. Numbers with 10 or 11
     * digits and no country code are treated as Brazilian numbers.
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '55') && in_array(strlen($digits), [12, 13], true)) {
            return '+'.$digits;
        }

        $length = strlen($digits);

        if ($length === 10) {
            return '+55'.$digits;
        }

        if ($length === 11) {
            $ddd = (int) substr($digits, 0, 2);
            $isBrazilianMobile = $ddd >= 11 && $ddd <= 99 && $digits[2] === '9';

            return $isBrazilianMobile ? '+55'.$digits : '+'.$digits;
        }

        if ($length >= 8 && $length <= 15) {
            return '+'.$digits;
        }

        return null;
    }

    /**
     * Formats an incoming E.164 number as the values commonly stored in
     * contacts.whatsapp, so webhook messages can match existing contacts.
     *
     * @return array<int, string>
     */
    public static function candidates(?string $phone): array
    {
        $normalized = self::normalize($phone);

        if ($normalized === null) {
            return [];
        }

        $digits = ltrim($normalized, '+');
        $candidates = [$normalized, $digits];

        if (str_starts_with($digits, '55') && in_array(strlen($digits), [12, 13], true)) {
            $national = substr($digits, 2);
            $candidates[] = $national;

            if (strlen($national) === 11) {
                $ddd = substr($national, 0, 2);
                $prefix = substr($national, 2, 5);
                $last = substr($national, 7);

                $candidates[] = "({$ddd}) {$prefix}-{$last}";
                $candidates[] = "{$ddd} {$prefix}-{$last}";
                $candidates[] = "{$ddd}{$prefix}-{$last}";
            }
        }

        return array_values(array_unique($candidates));
    }
}
