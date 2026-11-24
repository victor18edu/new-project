<?php

namespace App\Jobs;

use App\Contracts\WhatsAppGateway;
use App\Models\WhatsAppMessage;
use App\Support\WhatsAppPhone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $messageId) {}

    public function handle(WhatsAppGateway $gateway): void
    {
        $message = WhatsAppMessage::with('contact')->find($this->messageId);

        if (! $message
            || $message->direction !== WhatsAppMessage::DIRECTION_OUTBOUND
            || $message->status !== WhatsAppMessage::STATUS_QUEUED) {
            return;
        }

        $to = WhatsAppPhone::normalize($message->contact?->whatsapp);

        if ($to === null) {
            $this->markAsFailed($message, 'invalid_recipient');

            return;
        }

        // The POST is not retried automatically: a lost response could result
        // in the customer receiving the same message twice.
        try {
            $providerMessageId = $gateway->send($to, $message->body);
        } catch (Throwable $exception) {
            Log::error('Falha ao enviar mensagem pelo WhatsApp.', [
                'whatsapp_message_id' => $this->messageId,
                'exception' => $exception::class,
            ]);

            $this->markAsFailed($message, 'send_failed');

            return;
        }

        DB::transaction(function () use ($message, $providerMessageId): void {
            $echo = WhatsAppMessage::where('provider_message_id', $providerMessageId)
                ->lockForUpdate()
                ->first();

            $attributes = [
                'provider_message_id' => $providerMessageId,
                'sent_at' => now(),
            ];

            // The echo can arrive before the send response. Keep the row created
            // by the CRM so the API response still points to a live record.
            if ($echo && $echo->id !== $message->id) {
                $metadata = $echo->metadata ?? [];

                if ($message->user_id !== null) {
                    $metadata['source'] = 'crm';
                }

                $echoRank = WhatsAppMessage::STATUS_RANK[$echo->status] ?? 0;
                $currentRank = WhatsAppMessage::STATUS_RANK[$message->status] ?? 0;

                if ($echoRank > $currentRank) {
                    $attributes['status'] = $echo->status;
                }

                $attributes['metadata'] = $metadata;
                $attributes['sent_at'] = $echo->sent_at ?? now();

                $echo->delete();
            } else {
                $currentRank = WhatsAppMessage::STATUS_RANK[$message->status] ?? 0;
                $sentRank = WhatsAppMessage::STATUS_RANK[WhatsAppMessage::STATUS_SENT] ?? 0;

                if ($currentRank < $sentRank) {
                    $attributes['status'] = WhatsAppMessage::STATUS_SENT;
                }
            }

            $message->update($attributes);
            $message->refresh();
        });
    }

    private function markAsFailed(WhatsAppMessage $message, string $reason): void
    {
        $message->update([
            'status' => WhatsAppMessage::STATUS_FAILED,
            'metadata' => ['error' => $reason],
        ]);
    }
}
