<?php

namespace App\Actions;

use App\Contracts\WhatsAppGateway;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Support\WhatsAppPhone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DeleteWhatsAppMessage
{
    public function __construct(private readonly WhatsAppGateway $gateway) {}

    public function handle(WhatsAppMessage $message, User $deleter): string
    {
        $remoteStatus = $this->deleteOnWhatsApp($message);

        DB::transaction(function () use ($message, $deleter, $remoteStatus): void {
            $metadata = $message->metadata ?? [];
            $metadata['remote_delete'] = $remoteStatus;

            $message->forceFill([
                'deleted_by' => $deleter->id,
                'metadata' => $metadata,
            ])->save();
            $message->delete();
        });

        return $remoteStatus;
    }

    private function deleteOnWhatsApp(WhatsAppMessage $message): string
    {
        $providerMessageId = $message->provider_message_id;

        if (! is_string($providerMessageId) || $providerMessageId === '') {
            return 'skipped';
        }

        $remoteJid = $this->remoteJid($message);

        if ($remoteJid === null) {
            return 'skipped';
        }

        try {
            $this->gateway->deleteForEveryone(
                $providerMessageId,
                $remoteJid,
                $message->direction === WhatsAppMessage::DIRECTION_OUTBOUND,
            );
        } catch (Throwable $exception) {
            Log::warning('Falha ao apagar mensagem no WhatsApp.', [
                'whatsapp_message_id' => $message->id,
                'exception' => $exception::class,
            ]);

            return 'failed';
        }

        return 'deleted';
    }

    private function remoteJid(WhatsAppMessage $message): ?string
    {
        $metadata = $message->metadata ?? [];
        $phone = $message->direction === WhatsAppMessage::DIRECTION_OUTBOUND
            ? ($metadata['to'] ?? null)
            : ($metadata['from'] ?? null);

        if (! is_string($phone) || $phone === '') {
            $phone = WhatsAppPhone::normalize($message->contact?->whatsapp);
        }

        if ($phone === null || $phone === '') {
            return null;
        }

        if (str_contains($phone, '@')) {
            return $phone;
        }

        return ltrim($phone, '+').'@s.whatsapp.net';
    }
}
