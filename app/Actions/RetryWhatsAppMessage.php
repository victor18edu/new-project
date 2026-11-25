<?php

namespace App\Actions;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\WhatsAppMessage;

final class RetryWhatsAppMessage
{
    public function handle(WhatsAppMessage $message): WhatsAppMessage
    {
        abort_unless(
            $message->direction === WhatsAppMessage::DIRECTION_OUTBOUND
                && $message->status === WhatsAppMessage::STATUS_FAILED
                && $message->provider_message_id === null
                && ! $message->trashed(),
            422,
            'Esta mensagem não pode ser reenviada.'
        );

        $metadata = $message->metadata ?? [];
        unset($metadata['error']);
        $metadata['retry_attempts'] = (int) ($metadata['retry_attempts'] ?? 0) + 1;

        $message->update([
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'metadata' => $metadata,
        ]);

        SendWhatsAppMessageJob::dispatch((int) $message->getKey());

        return $message;
    }
}
