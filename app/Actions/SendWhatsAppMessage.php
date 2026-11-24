<?php

namespace App\Actions;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Support\WhatsAppPhone;
use RuntimeException;

final class SendWhatsAppMessage
{
    public function handle(Contact $contact, string $body, ?User $user = null): WhatsAppMessage
    {
        if (WhatsAppPhone::normalize($contact->whatsapp) === null) {
            throw new RuntimeException('O contato não possui um número de WhatsApp válido.');
        }

        $message = $contact->whatsappMessages()->create([
            'user_id' => $user?->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'body' => $body,
        ]);

        SendWhatsAppMessageJob::dispatch((int) $message->getKey());

        return $message;
    }
}
