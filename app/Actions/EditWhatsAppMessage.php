<?php

namespace App\Actions;

use App\Contracts\WhatsAppGateway;
use App\Exceptions\WhatsAppRemoteEditFailed;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Support\WhatsAppMessageTarget;
use Illuminate\Support\Facades\DB;
use Throwable;

final class EditWhatsAppMessage
{
    public function __construct(private readonly WhatsAppGateway $gateway) {}

    public function handle(WhatsAppMessage $message, string $body, User $editor): WhatsAppMessage
    {
        $remoteEdit = $this->applyRemoteEdit($message, $body);

        return DB::transaction(function () use ($message, $body, $editor, $remoteEdit): WhatsAppMessage {
            $metadata = $message->metadata ?? [];
            $metadata['remote_edit'] = $remoteEdit;

            $message->revisions()->create([
                'editor_id' => $editor->id,
                'old_body' => $message->body,
                'new_body' => $body,
            ]);

            $message->forceFill([
                'body' => $body,
                'edited_by' => $editor->id,
                'edited_at' => now(),
                'metadata' => $metadata,
            ])->save();

            return $message;
        });
    }

    private function applyRemoteEdit(WhatsAppMessage $message, string $body): string
    {
        $providerMessageId = $message->provider_message_id;

        if (! is_string($providerMessageId) || $providerMessageId === '') {
            return 'skipped';
        }

        $remoteJid = WhatsAppMessageTarget::remoteJid($message);
        $number = $remoteJid === null ? null : WhatsAppMessageTarget::number($remoteJid);

        if ($remoteJid === null || $number === null) {
            throw new WhatsAppRemoteEditFailed((int) $message->id);
        }

        try {
            $this->gateway->updateMessage($providerMessageId, $remoteJid, $number, $body);
        } catch (Throwable $exception) {
            throw new WhatsAppRemoteEditFailed((int) $message->id, $exception);
        }

        return 'edited';
    }
}
