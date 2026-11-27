<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.evolution.webhook_token', 'webhook-token');
    }

    public function test_webhook_rejects_invalid_token_without_persisting(): void
    {
        $this->contactWithWhatsapp();

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAEINVALID', 'remoteJid' => '5511877777777@s.whatsapp.net', 'fromMe' => false],
        ])]), 'invalid-token')->assertForbidden();

        $this->assertDatabaseCount('whatsapp_messages', 0);
        $this->assertDatabaseMissing('contacts', ['whatsapp' => '+5511877777777']);
    }

    public function test_stores_evolution_v2_inbound_message_for_matching_contact(): void
    {
        $contact = $this->contactWithWhatsapp();

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => [
                'id' => 'BAEINBOUND',
                'remoteJid' => '5511999999999@s.whatsapp.net',
                'fromMe' => false,
            ],
            'message' => ['conversation' => 'Olá, preciso de ajuda'],
        ])]))->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'provider_message_id' => 'BAEINBOUND',
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'status' => WhatsAppMessage::STATUS_RECEIVED,
            'body' => 'Olá, preciso de ajuda',
        ]);
        $this->assertNotNull(WhatsAppMessage::where('provider_message_id', 'BAEINBOUND')->firstOrFail()->sent_at);
    }

    public function test_stores_extended_text_message(): void
    {
        $contact = $this->contactWithWhatsapp();

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAELINK', 'remoteJid' => '5511999999999@lid', 'fromMe' => false],
            'message' => ['extendedTextMessage' => ['text' => 'Veja o link']],
        ])]))->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'provider_message_id' => 'BAELINK',
            'contact_id' => $contact->id,
            'body' => 'Veja o link',
        ]);
    }

    public function test_accepts_legacy_data_messages_payload(): void
    {
        $contact = $this->contactWithWhatsapp();

        $this->postWebhook($this->legacyUpsert([$this->inboundMessage([
            'key' => ['id' => 'BAELEGACY', 'remoteJid' => '5511999999999@s.whatsapp.net', 'fromMe' => false],
            'message' => ['conversation' => 'Payload legado'],
        ])]))->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'provider_message_id' => 'BAELEGACY',
            'contact_id' => $contact->id,
            'body' => 'Payload legado',
        ]);
    }

    public function test_audio_message_from_unknown_number_creates_contact_with_placeholder(): void
    {
        User::factory()->create(['role' => 'admin']);

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAEAUDIO', 'remoteJid' => '5511555555555@s.whatsapp.net', 'fromMe' => false],
            'message' => ['audioMessage' => ['mimetype' => 'audio/ogg; codecs=opus']],
            'messageType' => 'audioMessage',
            'pushName' => 'Thalysson',
        ])]))->assertOk();

        $contact = Contact::where('whatsapp', '+5511555555555')->firstOrFail();
        $message = WhatsAppMessage::where('provider_message_id', 'BAEAUDIO')->firstOrFail();

        $this->assertSame('Thalysson', $contact->name);
        $this->assertSame('[Áudio]', $message->body);
        $this->assertSame('audioMessage', $message->metadata['message_type']);
        $this->assertSame($contact->id, $message->contact_id);
    }

    public function test_image_message_for_matching_contact_is_stored_with_placeholder(): void
    {
        $contact = $this->contactWithWhatsapp();

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAEIMAGE', 'remoteJid' => '5511999999999@s.whatsapp.net', 'fromMe' => false],
            'message' => ['imageMessage' => ['mimetype' => 'image/jpeg']],
        ])]))->assertOk();

        $message = WhatsAppMessage::where('provider_message_id', 'BAEIMAGE')->firstOrFail();

        $this->assertSame('[Imagem]', $message->body);
        $this->assertSame('imageMessage', $message->metadata['message_type']);
        $this->assertSame($contact->id, $message->contact_id);
    }

    public function test_lid_jid_uses_alternate_phone_number(): void
    {
        User::factory()->create(['role' => 'admin']);

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => [
                'id' => 'BAELID',
                'remoteJid' => '180431693603063@lid',
                'remoteJidAlt' => '5511333333333@s.whatsapp.net',
                'fromMe' => false,
            ],
            'message' => ['conversation' => 'Mensagem via LID'],
        ])]))->assertOk();

        $this->assertDatabaseHas('contacts', ['whatsapp' => '+5511333333333']);
        $this->assertDatabaseMissing('contacts', ['whatsapp' => '+180431693603063']);
        $this->assertDatabaseHas('whatsapp_messages', [
            'provider_message_id' => 'BAELID',
            'body' => 'Mensagem via LID',
        ]);
    }

    public function test_duplicate_webhook_does_not_duplicate_message(): void
    {
        $this->contactWithWhatsapp();
        $payload = $this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAEDUP', 'remoteJid' => '5511999999999@s.whatsapp.net', 'fromMe' => false],
        ])]);

        $this->postWebhook($payload)->assertOk();
        $this->postWebhook($payload)->assertOk();

        $this->assertDatabaseCount('whatsapp_messages', 1);
    }

    public function test_from_me_message_creates_outbound_echo_for_the_recipient(): void
    {
        User::factory()->create(['role' => 'admin']);

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAEFROMME', 'remoteJid' => '5511866666666@s.whatsapp.net', 'fromMe' => true],
            'message' => ['conversation' => 'Respondido pelo celular'],
            'pushName' => 'Eu Mesmo',
        ])]))->assertOk();

        $contact = Contact::where('whatsapp', '+5511866666666')->firstOrFail();
        $message = WhatsAppMessage::where('provider_message_id', 'BAEFROMME')->firstOrFail();

        $this->assertSame('WhatsApp +5511866666666', $contact->name);
        $this->assertSame($contact->id, $message->contact_id);
        $this->assertNull($message->user_id);
        $this->assertSame(WhatsAppMessage::DIRECTION_OUTBOUND, $message->direction);
        $this->assertSame(WhatsAppMessage::STATUS_SENT, $message->status);
        $this->assertSame('Respondido pelo celular', $message->body);
        $this->assertSame('phone_echo', $message->metadata['source']);
    }

    public function test_from_me_echo_is_not_duplicated(): void
    {
        $contact = $this->contactWithWhatsapp();
        $payload = $this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAEECHO', 'remoteJid' => '5511999999999@s.whatsapp.net', 'fromMe' => true],
            'message' => ['conversation' => 'Echo repetido'],
        ])]);

        $this->postWebhook($payload)->assertOk();
        $this->postWebhook($payload)->assertOk();

        $this->assertSame(1, WhatsAppMessage::where('provider_message_id', 'BAEECHO')->count());
        $this->assertSame(1, Contact::count());
        $this->assertSame($contact->id, WhatsAppMessage::where('provider_message_id', 'BAEECHO')->value('contact_id'));
    }

    public function test_from_me_echo_reuses_message_sent_by_crm(): void
    {
        $contact = $this->contactWithWhatsapp();
        $user = User::factory()->create();
        $message = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'user_id' => $user->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_DELIVERED,
            'provider_message_id' => 'BAECRM',
            'body' => 'Mensagem do CRM',
        ]);

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAECRM', 'remoteJid' => '5511999999999@s.whatsapp.net', 'fromMe' => true],
            'message' => ['conversation' => 'Mensagem do CRM'],
        ])]))->assertOk();

        $this->assertSame(1, WhatsAppMessage::count());
        $this->assertSame($user->id, $message->fresh()->user_id);
        $this->assertSame(WhatsAppMessage::STATUS_DELIVERED, $message->fresh()->status);
    }

    public function test_from_me_media_echo_uses_placeholder(): void
    {
        User::factory()->create(['role' => 'admin']);

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAEECHOAUDIO', 'remoteJid' => '5511444444444@s.whatsapp.net', 'fromMe' => true],
            'message' => ['audioMessage' => ['mimetype' => 'audio/ogg']],
            'messageType' => 'audioMessage',
        ])]))->assertOk();

        $message = WhatsAppMessage::where('provider_message_id', 'BAEECHOAUDIO')->firstOrFail();

        $this->assertSame('[Áudio]', $message->body);
        $this->assertSame(WhatsAppMessage::DIRECTION_OUTBOUND, $message->direction);
        $this->assertSame('audioMessage', $message->metadata['message_type']);
    }

    public function test_status_update_advances_phone_echo(): void
    {
        User::factory()->create(['role' => 'admin']);

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAEECHOSTATUS', 'remoteJid' => '5511333333333@s.whatsapp.net', 'fromMe' => true],
        ])]))->assertOk();

        $message = WhatsAppMessage::where('provider_message_id', 'BAEECHOSTATUS')->firstOrFail();

        $this->postWebhook($this->statusUpdate('BAEECHOSTATUS', 'PLAYED'))->assertOk();

        $this->assertSame(WhatsAppMessage::STATUS_READ, $message->fresh()->status);
    }

    public function test_status_update_advances_outbound_message(): void
    {
        $contact = $this->contactWithWhatsapp();
        $message = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_SENT,
            'provider_message_id' => 'BAEOUT',
            'body' => 'Mensagem enviada',
        ]);

        $this->postWebhook($this->statusUpdate('BAEOUT', 'DELIVERY_ACK'))->assertOk();

        $this->assertSame(WhatsAppMessage::STATUS_DELIVERED, $message->fresh()->status);
    }

    public function test_played_status_from_evolution_v2_maps_to_read(): void
    {
        $contact = $this->contactWithWhatsapp();
        $message = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_DELIVERED,
            'provider_message_id' => 'BAEPLAYED',
            'body' => 'Mensagem reproduzida',
        ]);

        $this->postWebhook($this->statusUpdate('BAEPLAYED', 'PLAYED'))->assertOk();

        $this->assertSame(WhatsAppMessage::STATUS_READ, $message->fresh()->status);
    }

    public function test_accepts_legacy_status_update_payload(): void
    {
        $contact = $this->contactWithWhatsapp();
        $message = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_SENT,
            'provider_message_id' => 'BAELEGACYSTATUS',
            'body' => 'Mensagem legada',
        ]);

        $this->postWebhook($this->legacyStatusUpdate('BAELEGACYSTATUS', 'DELIVERY_ACK'))->assertOk();

        $this->assertSame(WhatsAppMessage::STATUS_DELIVERED, $message->fresh()->status);
    }

    public function test_older_status_does_not_downgrade_message(): void
    {
        $contact = $this->contactWithWhatsapp();
        $message = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_READ,
            'provider_message_id' => 'BAEREAD',
            'body' => 'Mensagem lida',
        ]);

        $this->postWebhook($this->statusUpdate('BAEREAD', 'SERVER_ACK'))->assertOk();

        $this->assertSame(WhatsAppMessage::STATUS_READ, $message->fresh()->status);
    }

    public function test_phone_delete_soft_deletes_without_removing_the_history(): void
    {
        $contact = $this->contactWithWhatsapp();
        $message = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_SENT,
            'provider_message_id' => 'BAEDEL',
            'body' => 'Mensagem apagada no celular',
        ]);

        $this->postWebhook([
            'event' => 'messages.delete',
            'instance' => 'crm',
            'data' => ['keys' => [['id' => 'BAEDEL']]],
        ])->assertOk();

        $this->assertSoftDeleted('whatsapp_messages', ['id' => $message->id]);
        $this->assertSame('phone', $message->fresh()->metadata['remote_delete_source']);
        $this->assertSame(1, WhatsAppMessage::withTrashed()->whereKey($message->id)->count());
    }

    public function test_revoke_update_from_phone_soft_deletes_the_original_message(): void
    {
        $contact = $this->contactWithWhatsapp();
        $message = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_SENT,
            'provider_message_id' => 'BAEREVOKE',
            'body' => 'Mensagem revogada',
        ]);

        $this->postWebhook([
            'event' => 'messages.update',
            'instance' => 'crm',
            'data' => [[
                'key' => ['id' => 'BAEEVENT'],
                'update' => [
                    'message' => [
                        'protocolMessage' => [
                            'key' => ['id' => 'BAEREVOKE'],
                            'type' => 'REVOKE',
                        ],
                    ],
                ],
            ]],
        ])->assertOk();

        $this->assertSoftDeleted('whatsapp_messages', ['id' => $message->id]);
        $this->assertSame('phone', $message->fresh()->metadata['remote_delete_source']);
    }

    public function test_phone_edit_updates_the_body_and_keeps_the_original_as_revision(): void
    {
        $contact = $this->contactWithWhatsapp();
        $message = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_SENT,
            'provider_message_id' => 'BAEEDIT',
            'body' => 'Texto original',
        ]);

        $this->postWebhook([
            'event' => 'messages.edited',
            'instance' => 'crm',
            'data' => [
                'key' => ['id' => 'BAEEDIT'],
                'message' => [
                    'protocolMessage' => [
                        'key' => ['id' => 'BAEEDIT'],
                        'type' => 'MESSAGE_EDIT',
                        'editedMessage' => ['conversation' => 'Texto editado no celular'],
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertSame('Texto editado no celular', $message->fresh()->body);
        $this->assertSame('phone', $message->fresh()->metadata['remote_edit_source']);
        $this->assertDatabaseHas('whatsapp_message_revisions', [
            'message_id' => $message->id,
            'editor_id' => null,
            'old_body' => 'Texto original',
            'new_body' => 'Texto editado no celular',
        ]);
    }

    public function test_phone_edit_does_not_overwrite_a_pending_local_edit(): void
    {
        $contact = $this->contactWithWhatsapp();
        $message = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_SENT,
            'provider_message_id' => 'BAEPENDING',
            'body' => 'Texto original',
            'metadata' => ['remote_edit' => 'failed'],
        ]);

        $this->postWebhook([
            'event' => 'messages.edited',
            'instance' => 'crm',
            'data' => [
                'key' => ['id' => 'BAEPENDING'],
                'message' => [
                    'protocolMessage' => [
                        'key' => ['id' => 'BAEPENDING'],
                        'type' => 'MESSAGE_EDIT',
                        'editedMessage' => ['conversation' => 'Texto do celular'],
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertSame('Texto original', $message->fresh()->body);
        $this->assertSame('failed', $message->fresh()->metadata['remote_edit']);
        $this->assertDatabaseCount('whatsapp_message_revisions', 0);
    }

    public function test_encrypted_phone_edit_does_not_overwrite_the_crm_text(): void
    {
        $contact = $this->contactWithWhatsapp();
        $message = WhatsAppMessage::create([
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'status' => WhatsAppMessage::STATUS_SENT,
            'provider_message_id' => 'BAEENCRYPTED',
            'body' => 'Texto original',
        ]);

        $this->postWebhook([
            'event' => 'messages.update',
            'instance' => 'crm',
            'data' => [[
                'key' => ['id' => 'BAEENCRYPTED'],
                'update' => [
                    'message' => [
                        'secretEncryptedMessage' => ['targetMessageKey' => ['id' => 'BAEENCRYPTED']],
                    ],
                ],
            ]],
        ])->assertOk();

        $this->assertSame('Texto original', $message->fresh()->body);
        $this->assertDatabaseCount('whatsapp_message_revisions', 0);
    }

    public function test_incoming_message_from_unknown_number_creates_contact(): void
    {
        User::factory()->create(['role' => 'admin']);

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAENEW', 'remoteJid' => '5511888888888@s.whatsapp.net', 'fromMe' => false],
            'message' => ['conversation' => 'Sou novo por aqui'],
            'pushName' => 'Maria Nova',
        ])]))->assertOk();

        $contact = Contact::where('whatsapp', '+5511888888888')->firstOrFail();
        $firstAdmin = User::query()->where('role', 'admin')->orderBy('id')->firstOrFail();

        $this->assertSame('Maria Nova', $contact->name);
        $this->assertTrue($contact->assignedUser->is($firstAdmin));
        $this->assertSame('WhatsApp (sistema)', $contact->company->trade_name);
        $this->assertDatabaseHas('whatsapp_messages', [
            'provider_message_id' => 'BAENEW',
            'contact_id' => $contact->id,
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'status' => WhatsAppMessage::STATUS_RECEIVED,
            'body' => 'Sou novo por aqui',
        ]);
    }

    public function test_created_contact_is_visible_to_admin_inbox(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAEINBOXNEW', 'remoteJid' => '5511666666666@s.whatsapp.net', 'fromMe' => false],
            'pushName' => 'Cliente Novo Inbox',
        ])]))->assertOk();

        $this->actingAs($admin)
            ->get(route('web.whatsapp.inbox'))
            ->assertOk()
            ->assertSee('Cliente Novo Inbox');
    }

    public function test_created_contact_is_reused_by_later_messages(): void
    {
        User::factory()->create(['role' => 'admin']);

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAENEW1', 'remoteJid' => '5511888888888@s.whatsapp.net', 'fromMe' => false],
        ])]))->assertOk();

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAENEW2', 'remoteJid' => '5511888888888@s.whatsapp.net', 'fromMe' => false],
            'message' => ['conversation' => 'Segunda mensagem'],
        ])]))->assertOk();

        $contact = Contact::where('whatsapp', '+5511888888888')->firstOrFail();

        $this->assertSame(1, Contact::where('whatsapp', '+5511888888888')->count());
        $this->assertSame(2, WhatsAppMessage::where('contact_id', $contact->id)->count());
    }

    public function test_alternate_stored_whatsapp_format_does_not_create_duplicate_contact(): void
    {
        $contact = $this->contactWithWhatsapp('(11) 99999-9999');
        $contactCount = Contact::count();

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAEFORMAT', 'remoteJid' => '5511999999999@s.whatsapp.net', 'fromMe' => false],
        ])]))->assertOk();

        $this->assertSame($contactCount, Contact::count());
        $this->assertDatabaseHas('whatsapp_messages', [
            'provider_message_id' => 'BAEFORMAT',
            'contact_id' => $contact->id,
        ]);
    }

    public function test_created_contact_falls_back_to_phone_number_when_push_name_is_missing(): void
    {
        User::factory()->create(['role' => 'admin']);

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAENONAME', 'remoteJid' => '5511777777777@s.whatsapp.net', 'fromMe' => false],
        ])]))->assertOk();

        $contact = Contact::where('whatsapp', '+5511777777777')->firstOrFail();

        $this->assertSame('WhatsApp +5511777777777', $contact->name);
    }

    public function test_group_messages_are_ignored(): void
    {
        $this->contactWithWhatsapp();
        $contactCount = Contact::count();

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAEGROUP', 'remoteJid' => '120363999@g.us', 'fromMe' => false],
        ])]))->assertOk();

        $this->assertDatabaseCount('whatsapp_messages', 0);
        $this->assertSame($contactCount, Contact::count());
    }

    public function test_group_media_messages_are_ignored(): void
    {
        $this->contactWithWhatsapp();
        $contactCount = Contact::count();

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAEGROUPAUDIO', 'remoteJid' => '120363999@g.us', 'fromMe' => false],
            'message' => ['audioMessage' => ['mimetype' => 'audio/ogg']],
            'messageType' => 'audioMessage',
        ])]))->assertOk();

        $this->assertDatabaseCount('whatsapp_messages', 0);
        $this->assertSame($contactCount, Contact::count());
    }

    public function test_from_me_group_messages_are_ignored(): void
    {
        $this->contactWithWhatsapp();
        $contactCount = Contact::count();

        $this->postWebhook($this->upsert([$this->inboundMessage([
            'key' => ['id' => 'BAEGROUPFROMME', 'remoteJid' => '120363999@g.us', 'fromMe' => true],
            'message' => ['conversation' => 'Mensagem em grupo'],
        ])]))->assertOk();

        $this->assertDatabaseCount('whatsapp_messages', 0);
        $this->assertSame($contactCount, Contact::count());
    }

    /**
     * Evolution v2 sends a single message object in `data`.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @return array<string, mixed>
     */
    private function upsert(array $messages): array
    {
        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'crm',
        ];

        if (count($messages) === 1) {
            $payload['data'] = $messages[0];
        } else {
            $payload['data'] = ['messages' => $messages];
        }

        return $payload;
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @return array<string, mixed>
     */
    private function legacyUpsert(array $messages): array
    {
        return [
            'event' => 'messages.upsert',
            'instance' => 'crm',
            'data' => ['messages' => $messages],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function inboundMessage(array $overrides = []): array
    {
        return array_replace([
            'key' => [
                'id' => 'BAEIN',
                'remoteJid' => '5511999999999@s.whatsapp.net',
                'fromMe' => false,
            ],
            'message' => ['conversation' => 'Mensagem recebida'],
            'messageTimestamp' => '1700000000',
        ], $overrides);
    }

    /**
     * Evolution v2 sends `keyId` and `status` directly in `data`.
     *
     * @return array<string, mixed>
     */
    private function statusUpdate(string $id, string $status): array
    {
        return [
            'event' => 'messages.update',
            'instance' => 'crm',
            'data' => [
                'keyId' => $id,
                'status' => $status,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyStatusUpdate(string $id, string $status): array
    {
        return [
            'event' => 'messages.update',
            'instance' => 'crm',
            'data' => [[
                'key' => ['id' => $id],
                'update' => ['status' => $status],
            ]],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postWebhook(array $payload, ?string $token = null): TestResponse
    {
        return $this->postJson('/api/v1/whatsapp/webhook', $payload, [
            'X-Evolution-Token' => $token ?? 'webhook-token',
        ]);
    }

    private function contactWithWhatsapp(string $whatsapp = '11999999999'): Contact
    {
        $company = Company::factory()->create();

        return Contact::create([
            'name' => 'Cliente Teste',
            'company_id' => $company->id,
            'whatsapp' => $whatsapp,
        ]);
    }
}
