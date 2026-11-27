<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Segment;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Support\WhatsAppPhone;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ProcessWhatsAppWebhook
{
    private const WHATSAPP_COMPANY = 'WhatsApp (sistema)';

    /**
     * Evolution reports delivery states using Baileys status names. Unknown
     * values are ignored so a later known state is still applied.
     */
    private const STATUS_MAP = [
        'ACKNOWLEDGED' => WhatsAppMessage::STATUS_SENT,
        'SERVER_ACK' => WhatsAppMessage::STATUS_SENT,
        'DELIVERY_ACK' => WhatsAppMessage::STATUS_DELIVERED,
        'READ' => WhatsAppMessage::STATUS_READ,
        'PLAYED' => WhatsAppMessage::STATUS_READ,
    ];

    /** Media is stored as a placeholder; downloading it is out of scope. */
    private const MEDIA_PLACEHOLDERS = [
        'audioMessage' => '[Áudio]',
        'imageMessage' => '[Imagem]',
        'videoMessage' => '[Vídeo]',
        'documentMessage' => '[Documento]',
        'stickerMessage' => '[Figurinha]',
    ];

    public function handle(array $payload): void
    {
        $event = $payload['event'] ?? null;
        $event = is_string($event) ? str_replace('_', '.', strtolower($event)) : null;
        $data = data_get($payload, 'data', []);

        if ($event === 'messages.upsert' && is_array($data)) {
            foreach ($this->normalizeMessages($data) as $message) {
                if ($this->isFromMe($message)) {
                    $this->storeOutboundEcho($message);
                } else {
                    $this->storeInbound($message);
                }
            }

            return;
        }

        if ($event === 'messages.delete' && is_array($data)) {
            foreach ($this->normalizeDeletedKeys($data) as $key) {
                $this->markDeletedFromPhone($this->providerIdFromKey($key));
            }

            return;
        }

        if ($event === 'messages.edited' && is_array($data)) {
            foreach ($this->normalizeMessages($data) as $message) {
                $this->applyEditedMessageFromPhone($message);
            }

            return;
        }

        if ($event === 'messages.update' && is_array($data)) {
            foreach ($this->normalizeUpdates($data) as $update) {
                $this->applyRemoteUpdate($update);
                $this->applyStatus($update);
            }
        }
    }

    /**
     * Evolution v2 sends one message object in `data`; older payloads send a
     * list either in `data` or in `data.messages`.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMessages(array $data): array
    {
        if (array_is_list($data)) {
            return array_values(array_filter($data, static fn (mixed $message): bool => is_array($message)));
        }

        $messages = data_get($data, 'messages');

        if (is_array($messages)) {
            if (array_is_list($messages)) {
                return array_values(array_filter($messages, static fn (mixed $message): bool => is_array($message)));
            }

            return [$messages];
        }

        return [$data];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function normalizeUpdates(array $data): array
    {
        if (array_is_list($data)) {
            return array_values(array_filter($data, static fn (mixed $update): bool => is_array($update)));
        }

        return [$data];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function normalizeDeletedKeys(array $data): array
    {
        $keys = data_get($data, 'keys');

        if (is_array($keys)) {
            $data = $keys;
        }

        if (! array_is_list($data)) {
            $data = [$data];
        }

        return array_values(array_filter($data, static fn (mixed $key): bool => is_array($key)));
    }

    /**
     * @param  array<string, mixed>  $keyLike
     */
    private function providerIdFromKey(array $keyLike): ?string
    {
        $id = data_get($keyLike, 'key.id') ?? data_get($keyLike, 'id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * Apply edits and revocations emitted by the connected phone. Status-only
     * updates are handled by applyStatus().
     *
     * @param  array<string, mixed>  $update
     */
    private function applyRemoteUpdate(array $update): void
    {
        $content = data_get($update, 'update.message');

        if (! is_array($content)) {
            $content = data_get($update, 'message');
        }

        $targetKey = data_get($content, 'protocolMessage.key')
            ?? data_get($update, 'update.protocolMessage.key')
            ?? data_get($update, 'key')
            ?? [];

        $targetId = is_array($targetKey) ? $this->providerIdFromKey($targetKey) : null;

        if ($targetId === null) {
            return;
        }

        if ($this->isRevoke(
            is_array($content) ? $content : [],
            data_get($update, 'update.messageStubType'),
            data_get($update, 'update.messageStubParameters'),
        )) {
            $this->markDeletedFromPhone($targetId);

            return;
        }

        if (! is_array($content)) {
            return;
        }

        $edited = data_get($content, 'protocolMessage.editedMessage');

        if (is_array($edited)) {
            $content = $edited;
        }

        $body = $this->messageContent(['message' => $content]);

        if ($body === null) {
            // Recent WhatsApp versions may deliver edits as an encrypted
            // envelope without the plaintext. Never overwrite the CRM with it.
            Log::info('Edição do WhatsApp sem texto recuperável foi ignorada.', [
                'provider_message_id' => $targetId,
            ]);

            return;
        }

        $this->applyEditedBody($targetId, $body['body']);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function applyEditedMessageFromPhone(array $message): void
    {
        $protocol = data_get($message, 'message.protocolMessage');
        $targetId = is_array($protocol)
            ? $this->providerIdFromKey(data_get($protocol, 'key') ?? [])
            : $this->providerIdFromKey($message);

        if ($targetId === null) {
            return;
        }

        $edited = is_array($protocol)
            ? data_get($protocol, 'editedMessage')
            : data_get($message, 'message');

        if (! is_array($edited)) {
            return;
        }

        $body = $this->messageContent(['message' => $edited]);

        if ($body === null) {
            Log::info('Edição do WhatsApp sem texto recuperável foi ignorada.', [
                'provider_message_id' => $targetId,
            ]);

            return;
        }

        $this->applyEditedBody($targetId, $body['body']);
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function isRevoke(array $content, mixed $stubType, mixed $stubParameters): bool
    {
        $type = data_get($content, 'protocolMessage.type');

        if ((is_string($type) && strtoupper($type) === 'REVOKE') || $type === 0) {
            return true;
        }

        if ((is_string($stubType) && strtoupper($stubType) === 'REVOKE') || $stubType === 1) {
            return true;
        }

        if (is_array($stubParameters)) {
            foreach ($stubParameters as $parameter) {
                if (is_string($parameter) && strtoupper($parameter) === 'REVOKE') {
                    return true;
                }
            }
        }

        return false;
    }

    private function applyEditedBody(string $providerMessageId, string $body): void
    {
        $message = WhatsAppMessage::where('provider_message_id', $providerMessageId)->first();

        if (! $message || $message->trashed() || $message->body === $body) {
            return;
        }

        $metadata = $message->metadata ?? [];

        // A local edit that could not reach WhatsApp must not be silently
        // replaced by a later phone event.
        if (($metadata['remote_edit'] ?? null) === 'failed') {
            return;
        }

        DB::transaction(function () use ($message, $body, $metadata): void {
            $message->revisions()->create([
                'editor_id' => null,
                'old_body' => $message->body,
                'new_body' => $body,
            ]);

            $metadata['remote_edit'] = 'edited';
            $metadata['remote_edit_source'] = 'phone';

            $message->forceFill([
                'body' => $body,
                'edited_at' => now(),
                'metadata' => $metadata,
            ])->save();
        });
    }

    private function markDeletedFromPhone(?string $providerMessageId): void
    {
        if ($providerMessageId === null) {
            return;
        }

        $message = WhatsAppMessage::withTrashed()
            ->where('provider_message_id', $providerMessageId)
            ->first();

        if (! $message || $message->trashed()) {
            return;
        }

        DB::transaction(function () use ($message): void {
            $metadata = $message->metadata ?? [];
            $metadata['remote_delete'] = 'deleted';
            $metadata['remote_delete_source'] = 'phone';

            // Soft delete keeps the message in the audit history.
            $message->forceFill(['metadata' => $metadata])->save();
            $message->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $update
     */
    private function applyStatus(array $update): void
    {
        $providerMessageId = data_get($update, 'key.id') ?? data_get($update, 'keyId');
        $reportedStatus = data_get($update, 'update.status') ?? data_get($update, 'status');

        if (! is_string($providerMessageId) || $providerMessageId === ''
            || ! is_string($reportedStatus)) {
            return;
        }

        $reportedStatus = strtoupper($reportedStatus);

        if (! array_key_exists($reportedStatus, self::STATUS_MAP)) {
            return;
        }

        $message = WhatsAppMessage::withTrashed()
            ->where('provider_message_id', $providerMessageId)
            ->first();

        if (! $message || $message->trashed()) {
            // Deleted messages are kept for audit only; webhooks must not
            // change or resurrect them.
            return;
        }

        $newStatus = self::STATUS_MAP[$reportedStatus];
        $currentRank = WhatsAppMessage::STATUS_RANK[$message->status] ?? 0;

        if (WhatsAppMessage::STATUS_RANK[$newStatus] <= $currentRank) {
            return;
        }

        $message->update(['status' => $newStatus]);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function isFromMe(array $message): bool
    {
        return data_get($message, 'key.fromMe') === true || data_get($message, 'fromMe') === true;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function storeInbound(array $message): void
    {
        $providerMessageId = data_get($message, 'key.id');
        $from = $this->phoneFromJid(
            data_get($message, 'key.remoteJid'),
            data_get($message, 'key.remoteJidAlt')
        );
        $content = $this->messageContent($message);

        if (! is_string($providerMessageId) || $providerMessageId === '' || $from === null || $content === null) {
            return;
        }

        $normalized = WhatsAppPhone::normalize($from);

        if ($normalized === null) {
            return;
        }

        DB::transaction(function () use ($providerMessageId, $normalized, $message, $content, $from): void {
            // A deleted message stays deleted; do not recreate it from a
            // retried webhook delivery.
            if (WhatsAppMessage::withTrashed()->where('provider_message_id', $providerMessageId)->exists()) {
                return;
            }

            $contact = $this->resolveContact($normalized, $message);

            WhatsAppMessage::create([
                'provider_message_id' => $providerMessageId,
                'contact_id' => $contact->id,
                'direction' => WhatsAppMessage::DIRECTION_INBOUND,
                'status' => WhatsAppMessage::STATUS_RECEIVED,
                'body' => $content['body'],
                'sent_at' => $this->timestamp(data_get($message, 'messageTimestamp')),
                'metadata' => ['from' => $from, 'message_type' => $content['type']],
            ]);
        });
    }

    /**
     * The connected number also echoes messages sent from the phone. They are
     * kept as outbound history instead of being discarded; the unique provider
     * id prevents duplicating messages already sent by the CRM.
     *
     * @param  array<string, mixed>  $message
     */
    private function storeOutboundEcho(array $message): void
    {
        $providerMessageId = data_get($message, 'key.id');
        $to = $this->phoneFromJid(
            data_get($message, 'key.remoteJid'),
            data_get($message, 'key.remoteJidAlt')
        );
        $content = $this->messageContent($message);

        if (! is_string($providerMessageId) || $providerMessageId === '' || $to === null || $content === null) {
            return;
        }

        $normalized = WhatsAppPhone::normalize($to);

        if ($normalized === null) {
            return;
        }

        DB::transaction(function () use ($providerMessageId, $normalized, $message, $content, $to): void {
            if (WhatsAppMessage::withTrashed()->where('provider_message_id', $providerMessageId)->exists()) {
                return;
            }

            // pushName identifies the phone owner, not the recipient.
            $contact = $this->resolveContact($normalized, []);

            WhatsAppMessage::create([
                'provider_message_id' => $providerMessageId,
                'contact_id' => $contact->id,
                'user_id' => null,
                'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
                'status' => WhatsAppMessage::STATUS_SENT,
                'body' => $content['body'],
                'sent_at' => $this->timestamp(data_get($message, 'messageTimestamp')),
                'metadata' => [
                    'to' => $to,
                    'message_type' => $content['type'],
                    'source' => 'phone_echo',
                ],
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array{body: string, type: string}|null
     */
    private function messageContent(array $message): ?array
    {
        $payload = data_get($message, 'message', []);

        if (! is_array($payload)) {
            return null;
        }

        $text = data_get($payload, 'conversation') ?? data_get($payload, 'extendedTextMessage.text');

        if (is_string($text) && trim($text) !== '') {
            return ['body' => $text, 'type' => 'text'];
        }

        $declaredType = data_get($message, 'messageType');

        if (is_string($declaredType) && isset(self::MEDIA_PLACEHOLDERS[$declaredType])) {
            return ['body' => self::MEDIA_PLACEHOLDERS[$declaredType], 'type' => $declaredType];
        }

        foreach (self::MEDIA_PLACEHOLDERS as $key => $placeholder) {
            if (array_key_exists($key, $payload)) {
                return ['body' => $placeholder, 'type' => $key];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function resolveContact(string $normalized, array $message): Contact
    {
        $candidates = WhatsAppPhone::candidates($normalized);

        // Re-check inside the caller's transaction so a retried delivery reuses one contact.
        $contact = Contact::whereIn('whatsapp', $candidates)->first();

        if ($contact) {
            return $contact;
        }

        $company = $this->systemCompany();

        return Contact::create([
            'name' => $this->contactName($message, $normalized),
            'company_id' => $company->id,
            'assigned_user_id' => $company->owner_id,
            'whatsapp' => $normalized,
            'phone' => $normalized,
            'notes' => 'Contato criado automaticamente a partir de mensagem recebida pelo WhatsApp.',
        ]);
    }

    private function systemCompany(): Company
    {
        return Company::firstOrCreate(
            ['trade_name' => self::WHATSAPP_COMPANY],
            [
                'corporate_name' => self::WHATSAPP_COMPANY,
                'segment_id' => Segment::firstOrCreate(
                    ['slug' => 'whatsapp'],
                    ['name' => 'WhatsApp', 'is_active' => true],
                )->id,
                'owner_id' => $this->ownerId(),
                'city' => 'Não informado',
                'state' => 'SP',
                'status' => 'prospecting',
                'temperature' => 'cold',
            ],
        );
    }

    private function ownerId(): ?int
    {
        $id = User::query()
            ->where('role', 'admin')
            ->orderBy('id')
            ->value('id')
            ?? User::query()->orderBy('id')->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function contactName(array $message, string $normalized): string
    {
        $pushName = data_get($message, 'pushName') ?? data_get($message, 'message.pushName');

        if (is_string($pushName) && trim($pushName) !== '') {
            return Str::limit(trim($pushName), 150);
        }

        return Str::limit('WhatsApp '.$normalized, 150);
    }

    private function phoneFromJid(mixed $jid, mixed $alternate = null): ?string
    {
        if (! is_string($jid) || $jid === ''
            || str_contains($jid, '@g.us')
            || str_contains($jid, '@broadcast')) {
            return null;
        }

        if (is_string($alternate) && $alternate !== '' && str_contains($jid, '@lid')) {
            $jid = $alternate;
        }

        $phone = Str::before($jid, '@');

        return $phone !== '' ? $phone : null;
    }

    private function timestamp(mixed $timestamp): ?Carbon
    {
        if (! is_string($timestamp) && ! is_int($timestamp)) {
            return null;
        }

        if (! ctype_digit((string) $timestamp)) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $timestamp);
    }
}
