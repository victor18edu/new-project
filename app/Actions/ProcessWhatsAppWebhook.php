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

        if ($event === 'messages.update' && is_array($data)) {
            foreach ($this->normalizeUpdates($data) as $update) {
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

        $message = WhatsAppMessage::where('provider_message_id', $providerMessageId)->first();

        if (! $message) {
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
            $contact = $this->resolveContact($normalized, $message);

            WhatsAppMessage::firstOrCreate(
                ['provider_message_id' => $providerMessageId],
                [
                    'contact_id' => $contact->id,
                    'direction' => WhatsAppMessage::DIRECTION_INBOUND,
                    'status' => WhatsAppMessage::STATUS_RECEIVED,
                    'body' => $content['body'],
                    'sent_at' => $this->timestamp(data_get($message, 'messageTimestamp')),
                    'metadata' => ['from' => $from, 'message_type' => $content['type']],
                ]
            );
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
            // pushName identifies the phone owner, not the recipient.
            $contact = $this->resolveContact($normalized, []);

            WhatsAppMessage::firstOrCreate(
                ['provider_message_id' => $providerMessageId],
                [
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
                ]
            );
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
