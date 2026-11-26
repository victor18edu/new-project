<?php

namespace App\Support;

use App\Models\WhatsAppMessage;

final class WhatsAppMessageTarget
{
    public static function remoteJid(WhatsAppMessage $message): ?string
    {
        $metadata = $message->metadata ?? [];
        $phone = $message->direction === WhatsAppMessage::DIRECTION_OUTBOUND
            ? ($metadata['to'] ?? null)
            : ($metadata['from'] ?? null);

        if (! is_string($phone) || $phone === '') {
            $phone = WhatsAppPhone::normalize($message->contact?->whatsapp);
        }

        if (! is_string($phone) || $phone === '') {
            return null;
        }

        if (str_contains($phone, '@')) {
            return $phone;
        }

        $normalized = WhatsAppPhone::normalize($phone);

        return $normalized === null ? null : ltrim($normalized, '+').'@s.whatsapp.net';
    }

    public static function number(?string $remoteJid): ?string
    {
        if ($remoteJid === null || $remoteJid === '') {
            return null;
        }

        [$number] = explode('@', $remoteJid, 2);
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        return $digits === '' ? null : $digits;
    }
}
